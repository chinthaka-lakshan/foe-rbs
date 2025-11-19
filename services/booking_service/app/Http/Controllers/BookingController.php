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
    // 
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

    /**
 * Verify booking with OTP
 */
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