<?php

namespace App\Http\Controllers;

use App\Models\BookingItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Exception;


class BookingItemController extends Controller
{
    /**
     * Get list of all booking items
     */
    public function index(): JsonResponse
    {
        $items = BookingItem::orderBy('name')->get();
        return response()->json($items);
    }

    /**
     * Get a single booking item by ID
     */
    public function show($id): JsonResponse
    {
        $item = BookingItem::findOrFail($id);
        return response()->json($item);
    }

    /**
     * Create a new booking item
     */
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'item_code' => 'nullable|string|unique:booking_items,item_code',
            'description' => 'nullable|string',
            'price_per_hour' => 'required|numeric|min:0',
            'available_quantity' => 'required|integer|min:1',
            'status' => 'required|in:Available,Unavailable,Maintenance',
        ]);

        DB::beginTransaction();
        try {
            // Generate item code if not provided
            if (empty($validatedData['item_code'])) {
                $validatedData['item_code'] = BookingItem::generateItemCode();
            }

            $item = BookingItem::create($validatedData);

            DB::commit();

            return response()->json([
                'message' => 'Booking item created successfully',
                'item' => $item
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            
            \Log::error("Booking item creation failed: " . $e->getMessage());
            
            return response()->json([
                'message' => 'Booking item creation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing booking item
     */
    public function update(Request $request, $id): JsonResponse
    {
        $item = BookingItem::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'item_code' => 'sometimes|string|unique:booking_items,item_code,' . $id,
            'description' => 'nullable|string',
            'price_per_hour' => 'sometimes|numeric|min:0',
            'available_quantity' => 'sometimes|integer|min:1',
            'status' => 'sometimes|in:Available,Unavailable,Maintenance',
        ]);

        DB::beginTransaction();
        try {
            $item->update($validatedData);

            DB::commit();

            return response()->json([
                'message' => 'Booking item updated successfully',
                'item' => $item
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            
            \Log::error("Booking item update failed: " . $e->getMessage());
            
            return response()->json([
                'message' => 'Booking item update failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a booking item
     */
    public function destroy($id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $item = BookingItem::findOrFail($id);
            $item->delete();

            DB::commit();

            return response()->json([
                'message' => 'Booking item deleted successfully'
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            
            \Log::error("Booking item deletion failed: " . $e->getMessage());
            
            return response()->json([
                'message' => 'Booking item deletion failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available booking items (for booking selection)
     */
    public function available(): JsonResponse
    {
        $items = BookingItem::where('status', 'Available')
            ->where('available_quantity', '>', 0)
            ->orderBy('name')
            ->get();

        return response()->json($items);
    }
}