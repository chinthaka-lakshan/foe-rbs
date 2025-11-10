<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Exception;
use App\Models\ResourceImage;

class ResourceController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        // 1. Validation
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'location_name' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'assigned_admin_id' => 'nullable|integer',
            'description' => 'nullable|string',
            'status' => 'required|in:Active,Inactive,Maintenance',
            
            // Validation for the file array received from the API Gateway
            'images' => 'nullable|array', 
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048', 
        ]);
        
        DB::beginTransaction();
        try {
            // 2. Create the Resource Record (use validated data directly)
            $resource = Resource::create($validatedData);

            // 3. Handle File Uploads and Association
            if ($request->hasFile('images')) {
                $imagesToSave = [];
                
                // Retrieve files and prepare image models
                foreach ($request->file('images') as $index => $file) {
                    
                    // Save the file to storage/app/public/resource_images
                    $path = Storage::disk('public')->putFile('resource_images', $file); 

                    $imagesToSave[] = new ResourceImage([
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'alt_text' => $validatedData['name'] . ' image ' . $index,
                        'order_index' => $index, // Use index for ordering
                    ]);
                }
                
                // Save all related image records in a single database operation
                $resource->resourceImages()->saveMany($imagesToSave);
            }

            DB::commit();
            
            // 4. Load relationships before returning (eager loading)
            $resource->load(['category', 'resourceImages']);
            
            return response()->json([
                'message' => 'Resource created successfully',
                'resource' => $resource
            ], 201);
            
        } catch (Exception $e) {
            DB::rollBack();
            
            // Log error with full stack trace for debugging
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