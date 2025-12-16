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
use App\Models\ResourceAvailability;

class ResourceController extends Controller
{
    // Get list of resources with related data
    public function index(): JsonResponse
    {
        $resources = Resource::with(['category', 'images', 'equipment', 'availability'])->get();
        return response()->json($resources);
    }

    // Get a single resource by ID with related data
    public function show($id): JsonResponse
    {
        $resource = Resource::with(['category', 'images', 'equipment', 'availability'])->findOrFail($id);
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
                // Nested data 
                
                'images' => 'nullable|array', 
                'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
                // Equipment validation
                'equipment' => 'nullable|array',
                'equipment.*.equipment_name' => 'required_with:equipment|string|max:255',
                'equipment.*.quantity' => 'required_with:equipment|integer|min:1',
                // Availability validation
                'availability' => 'nullable|array',
                'availability.*.day_of_week' => 'required_with:availability|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
                'availability.*.is_available' => 'required_with:availability|in:0,1,true,false',
                'availability.*.start_time' => 'nullable|date_format:H:i',
                'availability.*.end_time' => 'nullable|date_format:H:i|after:availability.*.start_time',
                
            ]);
            
            $equipmentData = $validatedData['equipment'] ?? [];
            $imagesData = $request->file('images') ?? [];
            $availabilityData = $validatedData['availability'] ?? [];
            

            $resourceData = collect($validatedData)->except(['images', 'equipment', 'availability'])->toArray();

            
            DB::beginTransaction();
            try {
                // 2. Create the Resource Record (base data)
                $resource = Resource::create($resourceData); 

                if($request->hasFile('images')){
                    $this->processImages($resource, $request->file('images'));
                }
                
                if (!empty($equipmentData)) {
                    $resource->equipment()->createMany($equipmentData);
                }
                if (!empty($availabilityData)) {
                    $availabilityToSave = [];
                    
                    foreach ($availabilityData as $availability) {
                        $dayName = $availability['day_of_week'];
                        $dayNumber = ResourceAvailability::getDayNumber($dayName);
                        
                        // Convert string boolean to actual boolean
                        $isAvailable = in_array($availability['is_available'], [true, 1, '1', 'true'], true);
                        
                        $availabilityToSave[] = [
                            'day_of_week' => $dayNumber,
                            'day_name' => $dayName,
                            'is_available' => $isAvailable,
                            'start_time' => $isAvailable && !empty($availability['start_time']) ? $availability['start_time'] : null,
                            'end_time' => $isAvailable && !empty($availability['end_time']) ? $availability['end_time'] : null,
                        ];
                    }
                    
                    $resource->availability()->createMany($availabilityToSave);
                }
                DB::commit();

                $resource->load(['category','images', 'equipment', 'availability']); 
                
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
            
            // Images
            'images' => 'sometimes|nullable|array',
            'images.*' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'removeImages' => 'sometimes|array',
            'removeImages.*' => 'sometimes|integer|exists:resource_images,id',
            
            // Equipment
            'equipment' => 'nullable|array',
            'equipment.*.id' => 'nullable|integer|exists:resource_equipment,id',
            'equipment.*.equipment_name' => 'required_with:equipment|string|max:255',
            'equipment.*.quantity' => 'required_with:equipment|integer|min:1',
            'delete_equipment' => 'nullable|array',
            'delete_equipment.*' => 'integer|exists:resource_equipment,id',
            
            // Availability
            'availability' => 'nullable|array',
            'availability.*.id' => 'nullable|integer|exists:resource_availability,id',
            'availability.*.day_of_week' => 'required_with:availability|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'availability.*.is_available' => 'required_with:availability|in:0,1,true,false',
            'availability.*.start_time' => 'nullable|date_format:H:i',
            'availability.*.end_time' => 'nullable|date_format:H:i',
        ]);

        $resourceData = collect($validatedData)->except([
            'images', 
            'equipment', 
            'removeImages', 
            'delete_equipment',
            'availability'
        ])->toArray();
        
        $imagesToRemoveIds = $validatedData['removeImages'] ?? [];
        $equipmentUpdates = $validatedData['equipment'] ?? [];
        $availabilityUpdates = $validatedData['availability'] ?? [];


        DB::beginTransaction();
        try {
            // Update base resource data
            $resource->update($resourceData);

            // Handle image deletions (LOGIC FIX APPLIED)
            if (!empty($imagesToRemoveIds)) {
                $imagesToDelete = ResourceImage::where('resource_id', $resource->id)
                    ->whereIn('id', $imagesToRemoveIds)
                    ->get();

                foreach ($imagesToDelete as $image) {
                    if (Storage::disk('public')->exists($image->file_path)) {
                        Storage::disk('public')->delete($image->file_path);
                    }
                    $image->delete();
                }
            }

            // Handle new image uploads
            if ($request->hasFile('images')) {
                $this->processImages($resource, $request->file('images'));
            }

            // Handle equipment deletions
            if (!empty($validatedData['delete_equipment'])) {
                ResourceEquipment::where('resource_id', $resource->id)
                    ->whereIn('id', $validatedData['delete_equipment'])
                    ->delete();
            }

            // Handle equipment updates/creates
            if (!empty($equipmentUpdates)) {
                foreach ($equipmentUpdates as $equipmentItem) {
                    if (isset($equipmentItem['id'])) {
                        // Update existing equipment
                        $equipment = ResourceEquipment::where('resource_id', $resource->id)
                            ->where('id', $equipmentItem['id'])
                            ->first();
                        
                        if ($equipment) {
                            $equipment->update([
                                'equipment_name' => $equipmentItem['equipment_name'],
                                'quantity' => $equipmentItem['quantity'],
                            ]);
                        }
                    } else {
                        // Create new equipment
                        $resource->equipment()->create([
                            'equipment_name' => $equipmentItem['equipment_name'],
                            'quantity' => $equipmentItem['quantity'],
                        ]);
                    }
                }
            }

            // Handle availability updates/creates
            if (!empty($availabilityUpdates)) {
                foreach ($availabilityUpdates as $availabilityItem) {
                    $dayName = $availabilityItem['day_of_week'];
                    $dayNumber = ResourceAvailability::getDayNumber($dayName);
                    $isAvailable = in_array($availabilityItem['is_available'], [true, 1, '1', 'true'], true);
                    
                    $availabilityData = [
                        'day_of_week' => $dayNumber,
                        'day_name' => $dayName,
                        'is_available' => $isAvailable,
                        'start_time' => $isAvailable && !empty($availabilityItem['start_time']) ? $availabilityItem['start_time'] : null,
                        'end_time' => $isAvailable && !empty($availabilityItem['end_time']) ? $availabilityItem['end_time'] : null,
                    ];
                    
                    if (isset($availabilityItem['id'])) {
                        // Update existing availability
                        $availability = ResourceAvailability::where('resource_id', $resource->id)
                            ->where('id', $availabilityItem['id'])
                            ->first();
                        
                        if ($availability) {
                            $availability->update($availabilityData);
                        }
                    } else {
                        // Create new availability or update by day (UPSERT)
                        ResourceAvailability::updateOrCreate(
                            [
                                'resource_id' => $resource->id,
                                'day_of_week' => $dayNumber,
                            ],
                            $availabilityData
                        );
                    }
                }
            }

            DB::commit();
            
            // Reload relationships
            $resource->load(['category', 'images', 'equipment', 'availability']);
            
            return response()->json([
                'message' => 'Resource updated successfully',
                'resource' => $resource
            ], 200);
            
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

    private function processImages(Resource $resource, array $images)
    {
        $currentCount = $resource->images()->count();

        if(($currentCount + count($images)) > 10){
            throw new Exception("Maximum of 10 images allowed per resource. Current:" . $currentCount);
        }
        $orderIndex = $resource->images()->max('order_index') ?? -1;

        foreach ($images as $image){
            $orderIndex++;

            $folderPath = 'resource_images/' . $resource->id;
            $filename = $this->generateUniqueFilename($image, $orderIndex);
            $path = $image->storeAs($folderPath, $filename, 'public');

            ResourceImage::create([
                'resource_id' => $resource->id,
                'file_path' => $path,
                'order_index' => $orderIndex,
                'alt_text' => $resource->name . ' image ' . $orderIndex,
            ]);
        }
    }

    private function generateUniqueFilename($file, $index)
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = time();
        return "image_{$index}_{$timestamp}.{$extension}";
    }

    private function reorderImages($resourceId)
    {
        $images = ResourceImage::where('resource_id', $resourceId)->orderBy('order_index')->get();
        foreach ($images as $index => $image) {
            $image->update(['order_index' => $index]);
        }
    }
    

    // Delete a resource
    public function destroy($id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $resource = Resource::with(['images', 'equipment', 'availability'])->findOrFail($id);
            
            // Delete all associated images from storage
            foreach ($resource->images as $image) {
                if (Storage::disk('public')->exists($image->file_path)) {
                    Storage::disk('public')->delete($image->file_path);
                }
            }
            
            // Delete the resource (cascade will handle images and equipment in DB)
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