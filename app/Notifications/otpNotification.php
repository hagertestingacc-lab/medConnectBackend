<?php

namespace App\Notifications;

use Ichtrojan\Otp\Otp ;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
class otpNotification extends Notification
{
    use Queueable;
 protected  $otp;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
        $this->otp=new Otp();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $otp =$this->otp->generate($notifiable->email,"numeric",4,60);
        return (new MailMessage)
        ->mailer("smtp")
        ->subject("Password resetting")
            ->line('Use the below code to reset the password')
            ->line('code: ' .  $otp->token)
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}