@component('mail::message')
# Rental Return Reminder

Dear Dr. {{ $doctor->name }},

This is a friendly reminder that your rental device is due for return in **2 days**.

@component('mail::panel')
**Order #:** {{ $order->id }}
**Return Date:** {{ $order->items->first()->rental_end->format('d M Y') }}
@endcomponent

Please arrange the return or renewal before the due date to avoid any late fees.

@component('mail::button', ['url' => config('app.url') . '/orders/' . $order->id])
View Order
@endcomponent

Thank you for using our platform.

Best regards,
{{ config('app.name') }} Team
@endcomponent
