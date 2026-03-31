<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingDetail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use App\Mail\BookingOTPMail;
use Carbon\Carbon;
use Exception;

class BookingController
{
    public function index(): JsonResponse
    {
        $bookings = Cache::tags(['bookings'])->remember('all_bookings', 60 * 60, function () {
            return Booking::with('details')->orderBy('booking_date', 'desc')->get();
        });
        return response()->json($bookings);
    }

    public function myBookings(Request $request): JsonResponse
    {
        $userEmail = $request->header('X-User-Email');
        
        if (!$userEmail) {
            return response()->json(['message' => 'User email missing from gateway'], 401);
        }

        $bookings = Booking::with('details')
            ->where('user_email', $userEmail)
            ->orderBy('booking_date', 'desc')
            ->get();
            
        return response()->json(['bookings' => $bookings]);
    }

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
                    $resourceDetails[] = array_merge($resource, [
                        'price_per_hour' => $detail->price_per_hour,
                        'hours' => $detail->hours,
                        'subtotal' => $detail->subtotal,
                    ]);
                }
            } elseif ($detail->item_type === 'booking_item') {
                $itemResponse = Http::timeout(10)->get("{$resourceServiceUrl}/booking-items/{$detail->item_id}");
                if ($itemResponse->successful()) {
                    $item = $itemResponse->json();
                    $bookingItemDetails[] = array_merge($item, [
                        'price_per_hour' => $detail->price_per_hour,
                        'quantity' => $detail->quantity,
                        'hours' => $detail->hours,
                        'subtotal' => $detail->subtotal,
                    ]);
                }
            }
        }

        return response()->json([
            'booking' => $booking,
            'resource_details' => $resourceDetails,
            'booking_item_details' => $bookingItemDetails,
        ]);
    }

    /**
     * Step 1: Create a Pending Booking and Send OTP
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer',
            'user_email' => 'required|email',
            'booking_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'notes' => 'nullable|string',
            'resources' => 'nullable|array',
            'resources.*.resource_id' => 'required_with:resources|integer',
            'booking_items' => 'nullable|array',
            'booking_items.*.item_id' => 'required_with:booking_items|integer',
            'booking_items.*.quantity' => 'required_with:booking_items|integer|min:1'
        ]);

        if (empty($validated['resources']) && empty($validated['booking_items'])) {
            return response()->json(['message' => 'At least one resource or item is required'], 422);
        }

        DB::beginTransaction();
        try {
            $otpCode = Booking::generateOTP();
            
            // 1. Get the user type BEFORE creating the record
            $userType = Booking::getUserType($validated['user_email']);

            // 2. Add 'user_type' to the create array
            $booking = Booking::create([
                'user_id'          => $validated['user_id'],
                'user_email'       => $validated['user_email'],
                'user_type'        => $userType, // Add this line here!
                'booking_reference'=> Booking::generateReference(),
                'booking_date'     => $validated['booking_date'],
                'start_time'       => $validated['start_time'],
                'end_time'         => $validated['end_time'],
                'total_amount'     => 0,
                'status'           => 'Pending_for_Verification',
                'is_verified'      => false,
                'notes'            => $validated['notes'] ?? null
            ]);

            // 2. Store original request data and OTP in cache linked to the ID
            Cache::put("booking_otp_{$booking->id}", [
                'data' => $validated,
                'otp' => $otpCode
            ], now()->addMinutes(10));

            Mail::to($validated['user_email'])->send(new BookingOTPMail($otpCode, "TEMP", 10));

            DB::commit();

            return response()->json([
                'message' => 'OTP sent to email.',
                'booking_id' => $booking->id,
                'otp_code_for_testing' => $otpCode // Remove in production
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to initiate booking', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Step 2: Verify OTP using {id} and Finalize Booking
     */
    public function verifyOTP(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'otp_code' => 'required|string|size:6'
        ]);

        $cacheKey = "booking_otp_{$id}";
        $cachedData = Cache::get($cacheKey);

        if (!$cachedData || $cachedData['otp'] !== $validated['otp_code']) {
            return response()->json(['message' => 'Invalid or expired OTP'], 422);
        }

        $booking = Booking::findOrFail($id);
        $data = $cachedData['data'];

        DB::beginTransaction();
        try {
            $userType = Booking::getUserType($data['user_email']);
            $start = Carbon::parse($data['start_time']);
            $end = Carbon::parse($data['end_time']);
            $hours = $start->diffInMinutes($end) / 60;

            $totalAmount = 0;
            $detailsToCreate = [];
            $resourceServiceUrl = env('RESOURCE_SERVICE_URL', 'http://resource_service/api');

            // Process Resources
            if (!empty($data['resources'])) {
                foreach ($data['resources'] as $resData) {
                    $resId = $resData['resource_id'];
                    $resResp = Http::get("{$resourceServiceUrl}/resources/{$resId}");
                    if (!$resResp->successful()) throw new Exception("Resource {$resId} not found");
                    
                    $res = $resResp->json();
                    $price = ($userType === 'internal') ? 0 : $res['base_price'];
                    $subtotal = $price * $hours;
                    $totalAmount += $subtotal;

                    $detailsToCreate[] = [
                        'item_type' => 'resource',
                        'item_id' => $resId,
                        'item_name' => $res['name'],
                        'quantity' => 1,
                        'price_per_hour' => $price,
                        'hours' => $hours,
                        'subtotal' => $subtotal
                    ];
                }
            }

            // Process Booking Items
            if (!empty($data['booking_items'])) {
                foreach ($data['booking_items'] as $itemData) {
                    $itemId = $itemData['item_id'];
                    $qty = $itemData['quantity'];
                    $itemResp = Http::get("{$resourceServiceUrl}/booking-items/{$itemId}");
                    if (!$itemResp->successful()) throw new Exception("Item {$itemId} not found");

                    $item = $itemResp->json();
                    $price = ($userType === 'internal') ? 0 : $item['price_per_hour'];
                    $subtotal = $price * $hours * $qty;
                    $totalAmount += $subtotal;

                    $detailsToCreate[] = [
                        'item_type' => 'booking_item',
                        'item_id' => $itemId,
                        'item_name' => $item['name'],
                        'item_code' => $item['item_code'],
                        'quantity' => $qty,
                        'price_per_hour' => $price,
                        'hours' => $hours,
                        'subtotal' => $subtotal
                    ];
                }
            }

            // Update original booking record to Confirmed status
            $booking->update([
                'status' => 'Confirmed',
                'total_amount' => $totalAmount,
                'is_verified' => true,
                'confirmed_at' => now(), // Add confirmed_at timestamp
                'user_type' => $userType
            ]);

            $booking->details()->createMany($detailsToCreate);
            Cache::forget($cacheKey);
            Cache::tags(['bookings'])->flush();

            DB::commit();
            return response()->json(['message' => 'Booking success!', 'booking' => $booking], 201);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Verification failed', 'error' => $e->getMessage()], 500);
        }
    }

    public function resendOTP(Request $request, $id): JsonResponse
    {
        $cacheKey = "booking_otp_{$id}";
        $cachedData = Cache::get($cacheKey);

        if (!$cachedData) {
            return response()->json(['message' => 'Session expired. Please restart booking.'], 422);
        }

        $otpCode = Booking::generateOTP();
        $cachedData['otp'] = $otpCode;
        Cache::put($cacheKey, $cachedData, now()->addMinutes(10));

        Mail::to($cachedData['data']['user_email'])->send(new BookingOTPMail($otpCode, "TEMP", 10));

        return response()->json(['message' => 'OTP resent successfully']);
    }

    public function updateStatus(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:Pending,Confirmed,Cancelled,Completed'
        ]);

        $booking = Booking::findOrFail($id);
        
        DB::beginTransaction();
        try {
            $booking->update(['status' => $validated['status']]);

            // CRITICAL: Clear the cache so the Admin Dashboard updates instantly
            Cache::tags(['bookings'])->flush();

            DB::commit();
            return response()->json([
                'message' => 'Status updated and cache cleared',
                'booking' => $booking
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Update failed'], 500);
        }
    }

    public function cancel($id): JsonResponse
    {
        $booking = Booking::findOrFail($id);
        if (in_array($booking->status, ['Cancelled', 'Completed'])) {
            return response()->json(['message' => 'Cannot cancel this booking'], 422);
        }
        $booking->update(['status' => 'Cancelled']);
        Cache::tags(['bookings'])->flush();
        return response()->json(['message' => 'Booking cancelled', 'booking' => $booking]);
    }

    public function getByAssignedAdmin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'admin_id' => 'required|integer',
            'status' => 'nullable|in:Pending,Confirmed,Cancelled,Completed',
        ]);

        $resourceServiceUrl = env('RESOURCE_SERVICE_URL', 'http://resource_service/api');
        $resourcesResp = Http::get("{$resourceServiceUrl}/resources");
        
        if (!$resourcesResp->successful()) return response()->json(['message' => 'Service error'], 500);

        $resourcesMap = collect($resourcesResp->json())->keyBy('id');
        $adminResourceIds = $resourcesMap->where('assigned_admin_id', $validated['admin_id'])->pluck('id')->toArray();

        $statusStr = $validated['status'] ?? 'all';
        $bookingsData = Cache::tags(['bookings'])->remember("bookings_admin_{$validated['admin_id']}_{$statusStr}", 60 * 60, function () use ($validated, $adminResourceIds) {
            $filtered = Booking::with('details')
                ->when($validated['status'] ?? null, fn($q, $s) => $q->where('status', $s))
                ->get()
                ->filter(function ($b) use ($adminResourceIds) {
                    return $b->details->contains(fn($d) => $d->item_type === 'resource' && in_array($d->item_id, $adminResourceIds));
                });
            return ['total' => $filtered->count(), 'bookings' => $filtered->values()];
        });

        return response()->json($bookingsData);
    }

    public function getByResourceId($resourceId): JsonResponse
    {
        $bookings = Booking::whereHas('details', function ($q) use ($resourceId) {
            $q->where('item_type', 'resource')->where('item_id', $resourceId);
        })->with('details')->orderBy('booking_date', 'desc')->get();

        return response()->json($bookings);
    }

    // public function destroy($id): JsonResponse
    // {
    //     $booking = Booking::findOrFail($id);
    //     $booking->delete();
    //     return response()->json(['message' => 'Booking deleted']);
    // }

    public function destroy($id): JsonResponse
    {
        $booking = Booking::findOrFail($id);

        DB::beginTransaction();
        try {
            // Delete children first to satisfy Foreign Key constraints
            $booking->details()->delete();
            $booking->delete();

            // CRITICAL: Clear the cache so the booking disappears from the list
            Cache::tags(['bookings'])->flush();

            DB::commit();
            return response()->json([
                'message' => 'Booking deleted and cache synchronized'
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Deletion failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}