<x-mail::message>
# New Guest Booking Request

A new guest booking has been requested and requires your attention.

**Booking Reference:** {{ $booking->booking_reference }}  
**Date:** {{ $booking->booking_date->format('Y-m-d') }}  
**Time:** {{ $booking->start_time }} to {{ $booking->end_time }}  
**Requested By:** {{ $booking->user_email }}

<x-mail::button :url="config('app.frontend_url', 'http://localhost:5173') . '/admin/dashboard'">
View Dashboard
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
