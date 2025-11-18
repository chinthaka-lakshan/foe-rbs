<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingDetail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Exception;

class BookingController
{
    /**
     * Get all bookings
     */
    public function index(): JsonResponse
    {
        $bookings = Booking::with('details')->orderBy('booking_date', 'desc')->get();
        return response()->json($bookings);
    }

    /**
     * Get single booking
     */
    public function show($id): JsonResponse
    {
        $booking = Booking::with('details')->findOrFail($id);
        return response()->json($booking);
    }

    /**
     * Create new booking
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer',
            'booking_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'notes' => 'nullable|string',
            
            'resources' => 'nullable|array',
            'resources.*.resource_id' => 'required_with:resources|integer',
            
            'booking_items' => 'nullable|array',
            'booking_items.*.item_id' => 'required_with:booking_items|integer',
            'booking_items.*.quantity' => 'required_with:booking_items|integer|min:1',
        ]);

        // At least one resource or booking item required
        if (empty($validated['resources']) && empty($validated['booking_items'])) {
            return response()->json([
                'message' => 'At least one resource or booking item is required'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Calculate duration
            $start = \Carbon\Carbon::parse($validated['start_time']);
            $end = \Carbon\Carbon::parse($validated['end_time']);
            $hours = $start->diffInMinutes($end) / 60;

            $totalAmount = 0;
            $detailsToCreate = [];

            $resourceServiceUrl = env('RESOURCE_SERVICE_URL', 'http://resource_service/api');

            // Process Resources
            if (!empty($validated['resources'])) {
                foreach ($validated['resources'] as $resourceData) {
                    $resourceId = $resourceData['resource_id'];
                    
                    // Get resource details
                    $resourceResponse = Http::timeout(10)->get("{$resourceServiceUrl}/resources/{$resourceId}");
                    
                    if (!$resourceResponse->successful()) {
                        DB::rollBack();
                        return response()->json([
                            'message' => "Resource ID {$resourceId} not found"
                        ], 404);
                    }

                    $resource = $resourceResponse->json();

                    // Check if resource is active
                    if ($resource['status'] !== 'Active') {
                        DB::rollBack();
                        return response()->json([
                            'message' => "Resource '{$resource['name']}' is not active"
                        ], 422);
                    }

                    // Check day availability
                    $dayOfWeek = \Carbon\Carbon::parse($validated['booking_date'])->format('l');
                    
                    $availability = collect($resource['availability'] ?? [])
                        ->firstWhere('day_name', $dayOfWeek);

                    if (!$availability || !$availability['is_available']) {
                        DB::rollBack();
                        return response()->json([
                            'message' => "Resource '{$resource['name']}' is not available on {$dayOfWeek}"
                        ], 422);
                    }

                    // Check time range
                    $requestStart = \Carbon\Carbon::parse($validated['start_time']);
                    $requestEnd = \Carbon\Carbon::parse($validated['end_time']);
                    $availableStart = \Carbon\Carbon::parse($availability['start_time']);
                    $availableEnd = \Carbon\Carbon::parse($availability['end_time']);

                    if ($requestStart->lt($availableStart) || $requestEnd->gt($availableEnd)) {
                        DB::rollBack();
                        return response()->json([
                            'message' => "Resource '{$resource['name']}' is only available from {$availability['start_time']} to {$availability['end_time']} on {$dayOfWeek}"
                        ], 422);
                    }

                    $pricePerHour = $resource['base_price'];
                    $subtotal = $pricePerHour * $hours;
                    $totalAmount += $subtotal;

                    $detailsToCreate[] = [
                        'item_type' => 'resource',
                        'item_id' => $resourceId,
                        'item_name' => $resource['name'],
                        'item_code' => null,
                        'quantity' => 1,
                        'price_per_hour' => $pricePerHour,
                        'hours' => $hours,
                        'subtotal' => $subtotal,
                    ];
                }
            }

            // Process Booking Items
            if (!empty($validated['booking_items'])) {
                foreach ($validated['booking_items'] as $itemData) {
                    $itemId = $itemData['item_id'];
                    $quantity = $itemData['quantity'];
                    
                    // Get booking item details
                    $itemResponse = Http::timeout(10)->get("{$resourceServiceUrl}/booking-items/{$itemId}");
                    
                    if (!$itemResponse->successful()) {
                        DB::rollBack();
                        return response()->json([
                            'message' => "Booking item ID {$itemId} not found"
                        ], 404);
                    }

                    $item = $itemResponse->json();

                    // Check if item is available
                    if ($item['status'] !== 'Available') {
                        DB::rollBack();
                        return response()->json([
                            'message' => "Booking item '{$item['name']}' is not available"
                        ], 422);
                    }

                    // Check quantity
                    if ($item['available_quantity'] < $quantity) {
                        DB::rollBack();
                        return response()->json([
                            'message' => "Booking item '{$item['name']}' doesn't have enough quantity. Available: {$item['available_quantity']}, Requested: {$quantity}"
                        ], 422);
                    }

                    $pricePerHour = $item['price_per_hour'];
                    $subtotal = $pricePerHour * $hours * $quantity;
                    $totalAmount += $subtotal;

                    $detailsToCreate[] = [
                        'item_type' => 'booking_item',
                        'item_id' => $itemId,
                        'item_name' => $item['name'],
                        'item_code' => $item['item_code'],
                        'quantity' => $quantity,
                        'price_per_hour' => $pricePerHour,
                        'hours' => $hours,
                        'subtotal' => $subtotal,
                    ];
                }
            }

            // Create booking
            $booking = Booking::create([
                'user_id' => $validated['user_id'],
                'booking_reference' => Booking::generateReference(),
                'booking_date' => $validated['booking_date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'total_amount' => $totalAmount,
                'status' => 'Pending',
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create booking details
            $booking->details()->createMany($detailsToCreate);

            DB::commit();

            // Load relationships
            $booking->load(['resources', 'bookingItems']);

            return response()->json([
                'message' => 'Booking created successfully',
                'booking' => $booking
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            
            \Log::error("Booking creation failed: " . $e->getMessage());
            
            return response()->json([
                'message' => 'Booking creation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update booking status
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:Pending,Confirmed,Cancelled,Completed',
        ]);

        $booking = Booking::findOrFail($id);
        $booking->update(['status' => $validated['status']]);

        return response()->json([
            'message' => 'Booking status updated successfully',
            'booking' => $booking
        ]);
    }

    /**
     * Cancel booking
     */
    public function cancel($id): JsonResponse
    {
        $booking = Booking::findOrFail($id);

        if ($booking->status === 'Cancelled') {
            return response()->json([
                'message' => 'Booking is already cancelled'
            ], 422);
        }

        if ($booking->status === 'Completed') {
            return response()->json([
                'message' => 'Cannot cancel completed booking'
            ], 422);
        }

        $booking->update(['status' => 'Cancelled']);

        return response()->json([
            'message' => 'Booking cancelled successfully',
            'booking' => $booking
        ]);
    }
}