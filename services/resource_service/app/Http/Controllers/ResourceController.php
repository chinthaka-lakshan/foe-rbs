<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ResourceController extends Controller
{
    /**
     * Store a newly created resource. (Add Resource)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location_name' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'assigned_admin_id' => 'nullable|integer',
            'description' => 'nullable|string',
        ]);

        $resource = Resource::create($validated);
        return response()->json($resource, 201);
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