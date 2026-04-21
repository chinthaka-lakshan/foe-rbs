<x-mail::message>
# Booking Status Update

Your booking status has been updated to **{{ $booking->status }}**.

**Booking Reference:** {{ $booking->booking_reference }}  
**Date:** {{ $booking->booking_date->format('Y-m-d') }}  
**Time:** {{ $booking->start_time }} to {{ $booking->end_time }}  

If you have any questions, please contact the administrator.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
