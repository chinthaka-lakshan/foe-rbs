<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingDetail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookingOTPMail;
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

    // public function index(): JsonResponse
    // {
    //     // 1. Fetch bookings from the local database
    //     $bookings = Booking::with('details')->orderBy('booking_date', 'desc')->get();

    //     // 2. Extract all unique resource IDs from the results
    //     $allResourceIds = $bookings->flatMap(function ($booking) {
    //         return $booking->details->where('item_type', 'resource')->pluck('item_id');
    //     })->unique()->filter()->values();

    //     $resourceMap = collect();

    //     // 3. One single HTTP call to get everything
    //     if ($allResourceIds->isNotEmpty()) {
    //         $resourceServiceUrl = env('RESOURCE_SERVICE_URL', 'http://resource_service/api');
            
    //         try {
    //             // We pass the IDs as a comma-separated string: ?ids=1,2,3
    //             $response = Http::timeout(5)->get("{$resourceServiceUrl}/resources/batch", [
    //                 'ids' => $allResourceIds->implode(',')
    //             ]);

    //             if ($response->successful()) {
    //                 // We key the collection by 'id' for ultra-fast lookup in Step 4
    //                 $resourceMap = collect($response->json())->keyBy('id');
    //             }
    //         } catch (\Exception $e) {
    //             \Log::error("Batch fetch failed: " . $e->getMessage());
    //         }
    //     }

    //     // 4. Combine the data in memory
    //     $formattedBookings = $bookings->map(function ($booking) use ($resourceMap) {
    //         $resourceDetails = [];
            
    //         foreach ($booking->details as $detail) {
    //             if ($detail->item_type === 'resource') {
    //                 $resourceData = $resourceMap->get($detail->item_id);
                    
    //                 $resourceDetails[] = [
    //                     'resource_id' => $detail->item_id,
    //                     'name'        => $resourceData['name'] ?? $detail->item_name,
    //                     'location'    => $resourceData['location_name'] ?? 'N/A',
    //                     'price'       => $detail->price_per_hour,
    //                     'subtotal'    => $detail->subtotal
    //                 ];
    //             }
    //         }

    //         return [
    //             'booking' => $booking->makeHidden('details'),
    //             'resource_details' => $resourceDetails
    //         ];
    //     });

    //     return response()->json($formattedBookings);
    // }

    /**
     * Get single booking
     */
    public function show($id): JsonResponse
    {
        $booking = Booking::with('details')->findOrFail($id);

        $resourceDetails = [];
        $bookingItemDetails = [];
        $resourceServiceUrl = env('RESOURCE_SERVICE_URL', 'http://resource_service/api');
        
        foreach ($booking->details as $detail) {
            if ($detail->item_type === 'resource') {
                $resourceResponse = Http::timeout(10)->get("{$resourceServiceUrl}/resources/{$detail->item_id}");
                if ($resourceResponse->successful()) {
                    $resource = $resourceResponse->json();
                    $resourceDetails[] = [
                        'resource_id' => $resource['id'],
                        'name' => $resource['name'],
                        'description' => $resource['description'] ?? null,
                        'location' => $resource['location'] ?? null,
                        'assigned_admin_id' => $resource['assigned_admin_id'] ?? null,
                        'assigned_admin_name' => $resource['assigned_admin_name'] ?? null,
                        'price_per_hour' => $detail->price_per_hour,
                        'hours' => $detail->hours,
                        'subtotal' => $detail->subtotal,
                    ];
                }
            } elseif ($detail->item_type === 'booking_item') {
                $itemResponse = Http::timeout(10)->get("{$resourceServiceUrl}/booking-items/{$detail->item_id}");
                if ($itemResponse->successful()) {
                    $item = $itemResponse->json();
                    $bookingItemDetails[] = [
                        'item_id' => $item['id'],
                        'name' => $item['name'],
                        'item_code' => $item['item_code'],
                        'price_per_hour' => $detail->price_per_hour,
                        'quantity' => $detail->quantity,
                        'hours' => $detail->hours,
                        'subtotal' => $detail->subtotal,
                    ];
                }
            }
        }

        return response()->json([
            'booking' => $booking,
            'resource_details' => $resourceDetails,
            'booking_item_details' => $bookingItemDetails,
        ]);
    }

    //create booking
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer',
            'user_email' => 'required|email', // Add email validation
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

        // Determine user type from email
        $userType = Booking::getUserType($validated['user_email']);

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
                    
                    $resourceResponse = Http::timeout(10)->get("{$resourceServiceUrl}/resources/{$resourceId}");
                    
                    if (!$resourceResponse->successful()) {
                        DB::rollBack();
                        return response()->json([
                            'message' => "Resource ID {$resourceId} not found"
                        ], 404);
                    }

                    $resource = $resourceResponse->json();

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

                    // Calculate price (Free for internal users)
                    $pricePerHour = ($userType === 'internal') ? 0 : $resource['base_price'];
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
                    
                    $itemResponse = Http::timeout(10)->get("{$resourceServiceUrl}/booking-items/{$itemId}");
                    
                    if (!$itemResponse->successful()) {
                        DB::rollBack();
                        return response()->json([
                            'message' => "Booking item ID {$itemId} not found"
                        ], 404);
                    }

                    $item = $itemResponse->json();

                    if ($item['status'] !== 'Available') {
                        DB::rollBack();
                        return response()->json([
                            'message' => "Booking item '{$item['name']}' is not available"
                        ], 422);
                    }

                    if ($item['available_quantity'] < $quantity) {
                        DB::rollBack();
                        return response()->json([
                            'message' => "Booking item '{$item['name']}' doesn't have enough quantity. Available: {$item['available_quantity']}, Requested: {$quantity}"
                        ], 422);
                    }

                    // Calculate price (Free for internal users)
                    $pricePerHour = ($userType === 'internal') ? 0 : $item['price_per_hour'];
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

            // Generate OTP
            $otpCode = Booking::generateOTP();
            $otpExpiresAt = now()->addMinutes(10); // OTP valid for 10 minutes

            // Create booking
            $booking = Booking::create([
                'user_id' => $validated['user_id'],
                'user_email' => $validated['user_email'],
                'user_type' => $userType,
                'is_verified' => false, // Needs OTP verification
                'otp_code' => $otpCode,
                'otp_expires_at' => $otpExpiresAt,
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

            // Send OTP via email (don't wait for it)
            try {
                Mail::to($validated['user_email'])->send(
                    new BookingOTPMail($otpCode, $booking->booking_reference, 10)
                );
            } catch (\Exception $e) {
                // Log but don't fail the booking
                \Log::error("Failed to send OTP email: " . $e->getMessage());
            }

            // Always log OTP for testing
            \Log::info("OTP for booking {$booking->booking_reference}: {$otpCode}");

            DB::commit();

            // Load relationships
            $booking->load('details');

            return response()->json([
                'message' => 'Booking created successfully. Please verify with OTP sent to your email.',
                'booking' => [
                    'id' => $booking->id,
                    'booking_reference' => $booking->booking_reference,
                    'user_email' => $booking->user_email,
                    'user_type' => $booking->user_type,
                    'is_verified' => $booking->is_verified,
                    'total_amount' => $booking->total_amount,
                    'status' => $booking->status,
                ],
                'requires_verification' => true,
                'otp_expires_in_minutes' => 10,
                // For testing only - remove in production!
                'otp_code_for_testing' => $otpCode
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

    //Verify booking with OTP

public function verifyOTP(Request $request, $id): JsonResponse
{
    $validated = $request->validate([
        'otp_code' => 'required|string|size:6',
    ]);

    $booking = Booking::findOrFail($id);

    // Check if already verified
    if ($booking->is_verified) {
        return response()->json([
            'message' => 'Booking is already verified'
        ], 422);
    }

    // Validate OTP
    if (!$booking->isOTPValid($validated['otp_code'])) {
        return response()->json([
            'message' => 'Invalid or expired OTP code'
        ], 422);
    }

    // Mark as verified
    $booking->update([
        'is_verified' => true,
        'otp_code' => null,
        'otp_expires_at' => null,
    ]);

    return response()->json([
        'message' => 'Booking verified successfully',
        'booking' => $booking
    ]);
}

/**
 * Resend OTP
 */
public function resendOTP($id): JsonResponse
{
    $booking = Booking::findOrFail($id);

    if ($booking->is_verified) {
        return response()->json([
            'message' => 'Booking is already verified'
        ], 422);
    }

    // Generate new OTP
    $otpCode = Booking::generateOTP();
    $otpExpiresAt = now()->addMinutes(10);

    $booking->update([
        'otp_code' => $otpCode,
        'otp_expires_at' => $otpExpiresAt,
    ]);

    // Send OTP via email
    try {
        Mail::to($booking->user_email)->send(
            new BookingOTPMail($otpCode, $booking->booking_reference, 10)
        );
        \Log::info("Resent OTP email to {$booking->user_email}");
    } catch (\Exception $e) {
        \Log::error("Failed to resend OTP email: " . $e->getMessage());
    }

    return response()->json([
        'message' => 'OTP has been resent to your email',
        'otp_expires_in_minutes' => 10,
        // For testing only - remove in production!
        'otp_code_for_testing' => $otpCode
    ]);
}

    // Update booking status
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


    // Cancel booking
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

    // Get bookings for resources assigned to a specific admin
    public function getByAssignedAdmin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'admin_id'   => 'required|integer',
            'status'     => 'nullable|in:Pending,Confirmed,Cancelled,Completed',
            'date_from'  => 'nullable|date',
            'date_to'    => 'nullable|date|after_or_equal:date_from',
        ]);

        $adminId = $validated['admin_id'];
        $resourceServiceUrl = env('RESOURCE_SERVICE_URL', 'http://resource_service/api');

        try {

            /** STEP 1 — Fetch resource list (only ONE API call) */
            $resourcesResponse = Http::timeout(10)->get("{$resourceServiceUrl}/resources");

            if (!$resourcesResponse->successful()) {
                return response()->json([
                    'message' => 'Failed to fetch resources',
                    'error'   => 'Resource service unavailable'
                ], 500);
            }

            $resourcesMap   = collect($resourcesResponse->json())->keyBy('id');
            $adminResourceIds = $resourcesMap
                ->where('assigned_admin_id', $adminId)
                ->pluck('id')
                ->toArray();

            if (empty($adminResourceIds)) {
                return response()->json([
                    'total' => 0,
                    'bookings' => [],
                    'message' => 'No resources assigned to this admin'
                ]);
            }

            /** STEP 2 — Get bookings with filters */
            $bookingsQuery = Booking::with('details');

            if (!empty($validated['status']))
                $bookingsQuery->where('status', $validated['status']);

            if (!empty($validated['date_from']))
                $bookingsQuery->where('booking_date', '>=', $validated['date_from']);

            if (!empty($validated['date_to']))
                $bookingsQuery->where('booking_date', '<=', $validated['date_to']);

            $allBookings = $bookingsQuery->orderBy('booking_date', 'desc')->get();

            /** STEP 3 — Filter bookings containing this admin’s resources */
            $adminBookings = $allBookings->filter(function ($booking) use ($adminResourceIds) {
                return $booking->details->contains(function ($d) use ($adminResourceIds) {
                    return $d->item_type === 'resource' && in_array($d->item_id, $adminResourceIds);
                });
            });

            /** STEP 4 — Format response output */
            $formattedBookings = $adminBookings->values()->map(function ($booking) use ($resourcesMap, $adminResourceIds) {

                $resourceDetails      = [];
                $bookingItemDetails   = [];

                foreach ($booking->details as $detail) {

                    /** RESOURCE ITEMS */
                    if ($detail->item_type === 'resource' && in_array($detail->item_id, $adminResourceIds)) {

                        $resource     = $resourcesMap->get($detail->item_id);
                        $adminDetails = $this->fetchAdminDetails($resource['assigned_admin_id'] ?? null);

                        $resourceDetails[] = [
                            'resource_id'          => $detail->item_id,
                            'name'                 => $resource['name'] ?? $detail->item_name,
                            'description'          => $resource['description'] ?? null,
                            'location'             => $resource['location_name'] ?? null,
                            'assigned_admin_id'    => $resource['assigned_admin_id'] ?? null,
                            'assigned_admin_name'  => $adminDetails['name'] ?? null,
                            'assigned_admin_email' => $adminDetails['email'] ?? null,
                            'price_per_hour'       => $detail->price_per_hour,
                            'hours'                => $detail->hours,
                            'subtotal'             => $detail->subtotal,
                        ];
                    }

                    /** OTHER BOOKING ITEMS */
                    elseif ($detail->item_type === 'booking_item') {
                        $bookingItemDetails[] = [
                            'item_id'        => $detail->item_id,
                            'name'           => $detail->item_name,
                            'item_code'      => $detail->item_code,
                            'price_per_hour' => $detail->price_per_hour,
                            'quantity'       => $detail->quantity,
                            'hours'          => $detail->hours,
                            'subtotal'       => $detail->subtotal,
                        ];
                    }
                }

                return [
                    'booking' => [
                        'id'               => $booking->id,
                        'booking_reference'=> $booking->booking_reference,
                        'user_id'          => $booking->user_id,
                        'user_email'       => $booking->user_email,
                        'user_type'        => $booking->user_type,
                        'booking_date'     => $booking->booking_date,
                        'start_time'       => $booking->start_time,
                        'end_time'         => $booking->end_time,
                        'total_amount'     => $booking->total_amount,
                        'status'           => $booking->status,
                        'is_verified'      => $booking->is_verified,
                        'notes'            => $booking->notes,
                        'created_at'       => $booking->created_at,
                    ],
                    'resource_details'      => $resourceDetails,
                    'booking_item_details'  => $bookingItemDetails,
                ];
            })

            /** STEP 5 — Sort so ADMIN-owned resource bookings appear first */
            ->sortByDesc(fn($b) => count($b['resource_details']) > 0)
            ->values();

            return response()->json([
                'total'    => $formattedBookings->count(),
                'bookings' => $formattedBookings
            ]);

        } catch (\Exception $e) {
            \Log::error("Failed to fetch admin bookings: " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch bookings',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // Helper to fetch admin details from User Service
    private function fetchAdminDetails($adminId)
    {
        if (empty($adminId)) {
            return null;
        }

        $userServiceUrl = env('USER_SERVICE_URL', 'http://user_service/api');

        try {
            $response = Http::timeout(10)->get("{$userServiceUrl}/users/{$adminId}");

            if ($response->successful()) {
                return $response->json();
            }

        } catch (\Exception $e) {
            \Log::error("Failed to fetch admin details for ID {$adminId}: " . $e->getMessage());
        }

        return null;
    }

    //get bookings by resource id
    public function getByResourceId($resourceId): JsonResponse
    {
        $bookings = Booking::whereHas('details', function ($query) use ($resourceId) {
            $query->where('item_type', 'resource')
                  ->where('item_id', $resourceId);
        })->with('details')->orderBy('booking_date', 'desc')->get();

        return response()->json($bookings);
    }

    //delete booking
    public function destroy($id): JsonResponse
    {
        $booking = Booking::findOrFail($id);
        $booking->delete();

        return response()->json([
            'message' => 'Booking deleted successfully'
        ]);
    }
}