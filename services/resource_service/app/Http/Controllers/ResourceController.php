<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Exception;
use App\Models\ResourceImage;
use App\Models\ResourceEquipment;

class ResourceController extends Controller
{
    // Get list of resources with related data
    public function index(): JsonResponse
    {
        $resources = Resource::with(['category', 'resourceImages', 'equipment'])->get();
        return response()->json($resources);
    }

    // Get a single resource by ID with related data
    public function show($id): JsonResponse
    {
        $resource = Resource::with(['category', 'resourceImages', 'equipment'])->findOrFail($id);
        return response()->json($resource);
    }

    public function store(Request $request): JsonResponse
    {
            // 1. Validation (remains correct)
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'location_name' => 'required|string',
                'category_id' => 'required|exists:categories,id',
                'assigned_admin_id' => 'nullable|integer',
                'description' => 'nullable|string',
                'base_price' => 'required|numeric|min:0',
                'status' => 'required|in:Active,Inactive,Maintenance',
                
                // Nested data validation
                'images' => 'nullable|array', 
                'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048', 
                'equipment' => 'nullable|array',
                'equipment.*.equipment_name' => 'required_with:equipment|string|max:255',
                'equipment.*.quantity' => 'required_with:equipment|integer|min:1',
                'equipment.*.equipment_price' => 'required_with:equipment|numeric|min:0',
            ]);
            
            // --- DATA SEPARATION (Corrected Variables) ---
            // Extract nested data arrays
            $equipmentData = $validatedData['equipment'] ?? [];
            $imagesData = $request->file('images') ?? []; // Use file() for file objects
            
            // 🛑 FIX: Get ONLY the fields belonging to the 'resources' table (excluding images and equipment)
            $resourceData = collect($validatedData)->except(['images', 'equipment'])->toArray();

            
            DB::beginTransaction();
            try {
                // 2. Create the Resource Record (base data)
                // 🚀 FIX: Use $resourceData, which contains only base columns, for creation.
                $resource = Resource::create($resourceData); 

                // 3. Handle File Uploads and Association (Images)
                if (!empty($imagesData)) {
                    $imagesToSave = [];
                    
                    foreach ($imagesData as $index => $file) {
                        // Note: Since $imagesData is from $request->file(), it contains file objects.
                        $path = Storage::disk('public')->putFile('resource_images', $file); 

                        $imagesToSave[] = new ResourceImage([
                            'file_path' => $path,
                            'file_name' => $file->getClientOriginalName(),
                            'alt_text' => $resource->name . ' image ' . $index,
                            'order_index' => $index,
                        ]);
                    }
                    $resource->resourceImages()->saveMany($imagesToSave);
                }
                
                // 4. Save the Equipment/Components
                if (!empty($equipmentData)) {
                    $resource->equipment()->createMany($equipmentData);
                }
                
                DB::commit();
                
                // 5. Load and Return
                $resource->load(['category', 'resourceImages', 'equipment']); 
                
                return response()->json([
                    'message' => 'Resource created successfully',
                    'resource' => $resource
                ], 201);
                
            } catch (Exception $e) {
                DB::rollBack();
                
                \Log::error("Resource creation failed: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
                
                return response()->json([
                    'message' => 'Resource creation failed. A transaction error occurred.',
                    'error' => $e->getMessage()
                ], 500);
            }
    }

    // Update an existing resource
    public function update(Request $request, $id): JsonResponse
    {
        $resource = Resource::findOrFail($id);
        
        // Validation
        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'location_name' => 'sometimes|string',
            'category_id' => 'sometimes|exists:categories,id',
            'assigned_admin_id' => 'nullable|integer',
            'description' => 'nullable|string',
            'base_price' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:Active,Inactive,Maintenance',
            
            // Images handling
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'delete_images' => 'nullable|array', // Array of image IDs to delete
            'delete_images.*' => 'integer|exists:resource_images,id',
            
            // Equipment handling
            'equipment' => 'nullable|array',
            'equipment.*.id' => 'nullable|integer|exists:resource_equipment,id', // For updates
            'equipment.*.equipment_name' => 'required_with:equipment|string|max:255',
            'equipment.*.quantity' => 'required_with:equipment|integer|min:1',
            'equipment.*.equipment_price' => 'required_with:equipment|numeric|min:0',
            'delete_equipment' => 'nullable|array', // Array of equipment IDs to delete
            'delete_equipment.*' => 'integer|exists:resource_equipment,id',
        ]);

        DB::beginTransaction();
        try {
            // 1. Update base resource data
            $resourceData = collect($validatedData)->except([
                'images', 
                'equipment', 
                'delete_images', 
                'delete_equipment'
            ])->toArray();
            
            $resource->update($resourceData);

            // 2. Handle image deletions
            if (!empty($validatedData['delete_images'])) {
                $imagesToDelete = ResourceImage::where('resource_id', $resource->id)
                    ->whereIn('id', $validatedData['delete_images'])
                    ->get();
                
                foreach ($imagesToDelete as $image) {
                    // Delete file from storage
                    if (Storage::disk('public')->exists($image->file_path)) {
                        Storage::disk('public')->delete($image->file_path);
                    }
                    $image->delete();
                }
            }

            // 3. Handle new image uploads
            if ($request->hasFile('images')) {
                $existingImagesCount = $resource->resourceImages()->count();
                $imagesToSave = [];
                
                foreach ($request->file('images') as $index => $file) {
                    $path = Storage::disk('public')->putFile('resource_images', $file);
                    
                    $imagesToSave[] = new ResourceImage([
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'alt_text' => $resource->name . ' image ' . ($existingImagesCount + $index),
                        'order_index' => $existingImagesCount + $index,
                    ]);
                }
                
                $resource->resourceImages()->saveMany($imagesToSave);
            }

            // 4. Handle equipment deletions
            if (!empty($validatedData['delete_equipment'])) {
                ResourceEquipment::where('resource_id', $resource->id)
                    ->whereIn('id', $validatedData['delete_equipment'])
                    ->delete();
            }

            // 5. Handle equipment updates/creates
            if (!empty($validatedData['equipment'])) {
                foreach ($validatedData['equipment'] as $equipmentItem) {
                    if (isset($equipmentItem['id'])) {
                        // Update existing equipment
                        $equipment = ResourceEquipment::where('resource_id', $resource->id)
                            ->where('id', $equipmentItem['id'])
                            ->first();
                        
                        if ($equipment) {
                            $equipment->update([
                                'equipment_name' => $equipmentItem['equipment_name'],
                                'quantity' => $equipmentItem['quantity'],
                                'equipment_price' => $equipmentItem['equipment_price'],
                            ]);
                        }
                    } else {
                        // Create new equipment
                        $resource->equipment()->create([
                            'equipment_name' => $equipmentItem['equipment_name'],
                            'quantity' => $equipmentItem['quantity'],
                            'equipment_price' => $equipmentItem['equipment_price'],
                        ]);
                    }
                }
            }

            DB::commit();
            
            // Reload relationships
            $resource->load(['category', 'resourceImages', 'equipment']);
            
            return response()->json([
                'message' => 'Resource updated successfully',
                'resource' => $resource
            ]);
            
        } catch (Exception $e) {
            DB::rollBack();
            
            \Log::error("Resource update failed: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Resource update failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Delete a resource
    public function destroy($id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $resource = Resource::with(['resourceImages', 'equipment'])->findOrFail($id);
            
            // 1. Delete all associated images from storage
            foreach ($resource->resourceImages as $image) {
                if (Storage::disk('public')->exists($image->file_path)) {
                    Storage::disk('public')->delete($image->file_path);
                }
            }
            
            // 2. Delete the resource (cascade will handle images and equipment in DB)
            $resource->delete();
            
            DB::commit();
            
            return response()->json([
                'message' => 'Resource deleted successfully'
            ], 200);
            
        } catch (Exception $e) {
            DB::rollBack();
            
            \Log::error("Resource deletion failed: " . $e->getMessage());
            
            return response()->json([
                'message' => 'Resource deletion failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}