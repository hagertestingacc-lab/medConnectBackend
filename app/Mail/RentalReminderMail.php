<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RentalReminderMail extends Mailable
{
    use SerializesModels;

    public function __construct(public Order $order)
    {
    }

    public function build()
    {
        return $this->subject('Reminder: Your Rental Device is Due Soon')
            ->markdown('emails.rental-reminder', [
                'order' => $this->order,
                'doctor' => $this->order->doctor,
            ]);
    }
}
