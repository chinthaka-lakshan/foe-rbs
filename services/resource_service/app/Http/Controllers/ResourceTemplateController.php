<?php

namespace App\Http\Controllers;

use App\Models\ResourceTemplate;
use App\Models\TemplateField;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Exception;

class ResourceTemplateController extends Controller
{
    /**
     * Get all templates
     */
    public function index(): JsonResponse
    {
        $templates = ResourceTemplate::with(['category', 'fields'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json($templates);
    }

    /**
     * Get single template
     */
    public function show($id): JsonResponse
    {
        $template = ResourceTemplate::with(['category', 'fields'])->findOrFail($id);
        return response()->json($template);
    }

    /**
     * Get templates by category
     */
    public function getByCategory($categoryId): JsonResponse
    {
        $templates = ResourceTemplate::with('fields')
            ->where('category_id', $categoryId)
            ->where('status', 'Active')
            ->get();
        
        return response()->json($templates);
    }

    /**
     * Create new template
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'template_name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
            'created_by' => 'required|integer',
            
            'fields' => 'required|array|min:1',
            'fields.*.field_name' => 'required|string|max:255',
            'fields.*.field_type' => 'required|in:text,number,textarea,checkbox,image',
            'fields.*.is_required' => 'sometimes|boolean',
            'fields.*.placeholder' => 'nullable|string',
            'fields.*.default_value' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Create template
            $template = ResourceTemplate::create([
                'template_name' => $validated['template_name'],
                'category_id' => $validated['category_id'],
                'description' => $validated['description'] ?? null,
                'status' => $validated['status'],
                'created_by' => $validated['created_by'],
            ]);

            // Create fields
            $fieldsToCreate = [];
            foreach ($validated['fields'] as $index => $fieldData) {
                $fieldKey = TemplateField::generateFieldKey($fieldData['field_name']);
                
                $fieldsToCreate[] = [
                    'template_id' => $template->id,
                    'field_name' => $fieldData['field_name'],
                    'field_key' => $fieldKey,
                    'field_type' => $fieldData['field_type'],
                    'is_required' => $fieldData['is_required'] ?? false,
                    'order_index' => $index,
                    'placeholder' => $fieldData['placeholder'] ?? null,
                    'default_value' => $fieldData['default_value'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            TemplateField::insert($fieldsToCreate);

            DB::commit();

            // Load relationships
            $template->load(['category', 'fields']);

            return response()->json([
                'message' => 'Template created successfully',
                'template' => $template
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            
            \Log::error("Template creation failed: " . $e->getMessage());
            
            return response()->json([
                'message' => 'Template creation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update template
     */
    public function update(Request $request, $id): JsonResponse
    {
        $template = ResourceTemplate::findOrFail($id);

        $validated = $request->validate([
            'template_name' => 'sometimes|string|max:255',
            'category_id' => 'sometimes|exists:categories,id',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:Active,Inactive',
            
            'fields' => 'sometimes|array',
            'fields.*.id' => 'nullable|integer|exists:template_fields,id',
            'fields.*.field_name' => 'required_with:fields|string|max:255',
            'fields.*.field_type' => 'required_with:fields|in:text,number,textarea,checkbox,image',
            'fields.*.is_required' => 'sometimes|boolean',
            'fields.*.placeholder' => 'nullable|string',
            'fields.*.default_value' => 'nullable|string',
            
            'delete_fields' => 'nullable|array',
            'delete_fields.*' => 'integer|exists:template_fields,id',
        ]);

        DB::beginTransaction();
        try {
            // Update template basic info
            $template->update([
                'template_name' => $validated['template_name'] ?? $template->template_name,
                'category_id' => $validated['category_id'] ?? $template->category_id,
                'description' => $validated['description'] ?? $template->description,
                'status' => $validated['status'] ?? $template->status,
            ]);

            // Delete fields if requested
            if (!empty($validated['delete_fields'])) {
                TemplateField::whereIn('id', $validated['delete_fields'])
                    ->where('template_id', $template->id)
                    ->delete();
            }

            // Update or create fields
            if (!empty($validated['fields'])) {
                foreach ($validated['fields'] as $index => $fieldData) {
                    $fieldKey = TemplateField::generateFieldKey($fieldData['field_name']);
                    
                    $fieldDataToSave = [
                        'field_name' => $fieldData['field_name'],
                        'field_key' => $fieldKey,
                        'field_type' => $fieldData['field_type'],
                        'is_required' => $fieldData['is_required'] ?? false,
                        'order_index' => $index,
                        'placeholder' => $fieldData['placeholder'] ?? null,
                        'default_value' => $fieldData['default_value'] ?? null,
                    ];

                    if (isset($fieldData['id'])) {
                        // Update existing field
                        TemplateField::where('id', $fieldData['id'])
                            ->where('template_id', $template->id)
                            ->update($fieldDataToSave);
                    } else {
                        // Create new field
                        $template->fields()->create($fieldDataToSave);
                    }
                }
            }

            DB::commit();

            // Load relationships
            $template->load(['category', 'fields']);

            return response()->json([
                'message' => 'Template updated successfully',
                'template' => $template
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            
            \Log::error("Template update failed: " . $e->getMessage());
            
            return response()->json([
                'message' => 'Template update failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete template
     */
    public function destroy($id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $template = ResourceTemplate::findOrFail($id);

            // Check if template is used by any resources
            $resourceCount = $template->resources()->count();
            
            if ($resourceCount > 0) {
                return response()->json([
                    'message' => "Cannot delete template. It is used by {$resourceCount} resource(s)."
                ], 422);
            }

            $template->delete();

            DB::commit();

            return response()->json([
                'message' => 'Template deleted successfully'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            
            \Log::error("Template deletion failed: " . $e->getMessage());
            
            return response()->json([
                'message' => 'Template deletion failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}