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

    /**
     * Display a listing of the resource. (List Resources)
     */
    public function index(): JsonResponse
    {
        $resources = Resource::with('category')->get();
        return response()->json($resources);
    }

    /**
     * Update the specified resource. (Edit Resource)
     */
    public function update(Request $request, Resource $resource): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'location_name' => 'sometimes|string',
            'category_id' => 'sometimes|exists:categories,id',
            'assigned_admin_id' => 'nullable|integer',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:Active,Inactive,Maintenance',
        ]);

        $resource->update($validated);
        return response()->json($resource);
    }
    
    /**
     * Remove the specified resource. (Delete Resource)
     */
    public function destroy(Resource $resource): JsonResponse
    {
        $resource->delete();
        return response()->json(null, 204);
    }
}