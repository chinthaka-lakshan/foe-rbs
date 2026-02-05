<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use App\Models\ResourceImage;
use App\Models\ResourceEquipment;
use App\Models\ResourceAvailability;
use App\Models\ResourceAvailabilitySlots;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Exception;

class ResourceController extends Controller
{
    // List all resources with related data
    public function index(): JsonResponse
    {
        $resources = Resource::with(['category', 'images', 'equipment', 'availability.slots'])->get();
        return response()->json($resources);
    }

    // Show a specific resource with related data
    public function show($id): JsonResponse
    {
        $resource = Resource::with(['category', 'images', 'equipment', 'availability.slots'])->findOrFail($id);
        return response()->json($resource);
    }

    // Create a new resource with multiple time slots
    public function store(Request $request): JsonResponse
    {
        // Validate input
        $validatedData = $this->validateResource($request);
        
        // Use transaction to ensure data integrity
        DB::beginTransaction();
        try {
            $resourceData = collect($validatedData)->except(['images', 'equipment', 'availability'])->toArray();
            $resource = Resource::create($resourceData);

            // Handle Image Uploads
            if ($request->hasFile('images')) {
                $this->processImages($resource, $request->file('images'));
            }

            // Handle Equipment
            if (!empty($validatedData['equipment'])) {
                $resource->equipment()->createMany($validatedData['equipment']);
            }

            // Handle Availability and Slots
            if (!empty($validatedData['availability'])) {
                $this->syncAvailability($resource, $validatedData['availability']);
            }

            DB::commit();
            return response()->json([
                'message' => 'Resource created successfully',
                'resource' => $resource->load(['category', 'images', 'equipment', 'availability.slots'])
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Creation failed', 'error' => $e->getMessage()], 500);
        }
    }

    // Update an existing resource with multiple time slots
    public function update(Request $request, $id): JsonResponse
    {
        // Validate input
        $resource = Resource::findOrFail($id);
        $validatedData = $this->validateResource($request, true);

        DB::beginTransaction();
        try {
            // Update base resource data
            $resource->update(collect($validatedData)->except(['images', 'equipment', 'availability', 'removeImages', 'delete_equipment'])->toArray());

            // Handle Image Deletions
            if (!empty($request->removeImages)) {
                $images = ResourceImage::where('resource_id', $id)->whereIn('id', $request->removeImages)->get();
                foreach ($images as $img) {
                    Storage::disk('public')->delete($img->file_path);
                    $img->delete();
                }
            }

            // Handle new image uploads
            if ($request->hasFile('images')) {
                $this->processImages($resource, $request->file('images'));
            }

            // Sync Availability and Slots
            if (isset($validatedData['availability'])) {
                $this->syncAvailability($resource, $validatedData['availability']);
            }

            DB::commit();
            return response()->json([
                'message' => 'Resource updated successfully',
                'resource' => $resource->load(['category', 'images', 'equipment', 'availability.slots'])
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Update failed', 'error' => $e->getMessage()], 500);
        }
    }

    // Sync Resource Availability and Slots
    private function syncAvailability(Resource $resource, array $availabilityData)
    {
        foreach ($availabilityData as $data) {
            // Use 'day_of_week' as the name if 'day_name' is missing from the request
            $dayName = $data['day_name'] ?? $data['day_of_week']; 
            
            $availability = $resource->availability()->updateOrCreate(
                ['day_name' => $dayName],
                [
                    'day_of_week' => ResourceAvailability::getDayNumber($dayName),
                    'is_available' => filter_var($data['is_available'], FILTER_VALIDATE_BOOLEAN),
                    'day_name' => $dayName
                ]
            );

            $availability->slots()->delete();
            // Add new slots if available
            if ($availability->is_available && !empty($data['slots'])) {
                foreach ($data['slots'] as $slot) {
                    $availability->slots()->create([
                        'start_time' => $slot['start_time'],
                        'end_time' => $slot['end_time'],
                    ]);
                }
            }
        }
    }
    
    // Validate resource input data
    private function validateResource(Request $request, $isUpdate = false)
    {
        // Validation rules
        return $request->validate([
            'name' => ($isUpdate ? 'sometimes' : 'required') . '|string|max:255',
            'location_name' => ($isUpdate ? 'sometimes' : 'required') . '|string',
            'category_id' => ($isUpdate ? 'sometimes' : 'required') . '|exists:categories,id',
            'base_price' => ($isUpdate ? 'sometimes' : 'required') . '|numeric|min:0',
            'status' => ($isUpdate ? 'sometimes' : 'required') . '|in:Active,Inactive,Maintenance',
            'description' => 'nullable|string',
            'assigned_admin_id' => 'nullable|integer',
            'availability' => 'nullable|array',
            'availability.*.day_of_week' => 'required_with:availability|string',
            'availability.*.is_available' => 'required_with:availability',
            'availability.*.slots' => 'present|array',
            'availability.*.slots.*.start_time' => 'required|date_format:H:i',
            'availability.*.slots.*.end_time' => 'required|date_format:H:i|after:availability.*.slots.*.start_time',
            'equipment' => 'nullable|array',
            'equipment.*.equipment_name' => 'required_with:equipment|string',
            'equipment.*.quantity' => 'required_with:equipment|integer',
        ]);
    }

    // Process and store uploaded images
    private function processImages(Resource $resource, array $images)
    {
        // Enforce maximum of 10 images
        if (($resource->images()->count() + count($images)) > 10) {
            throw new Exception("Maximum 10 images allowed.");
        }

        foreach ($images as $image) {
            $path = $image->store('resource_images/' . $resource->id, 'public');
            $resource->images()->create([
                'file_path' => $path,
                'order_index' => $resource->images()->max('order_index') + 1,
                'alt_text' => $resource->name
            ]);
        }
    }

    // Delete a resource and its associated images
    public function destroy($id): JsonResponse
    {
        $resource = Resource::with('images')->findOrFail($id);
        foreach ($resource->images as $image) {
            Storage::disk('public')->delete($image->file_path);
        }

        $resource->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }

    // Get resources in batch by IDs
    public function getBatch(Request $request): JsonResponse
    {
        $idsString = $request->query('ids');
        if (!$idsString) return response()->json(['message' => 'No IDs provided'], 400);
        $ids = explode(',', $idsString);
        $resources = Resource::with(['category', 'images', 'equipment', 'availability.slots'])->whereIn('id', $ids)->get();
        return response()->json($resources);
    }
}