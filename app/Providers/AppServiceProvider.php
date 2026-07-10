<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
       
        Broadcast::routes(['middleware' => ['auth:sanctum']]);
        VerifyEmail::toMailUsing(function($notifiable,$url)
        {
            return (new MailMessage())->subject('Verify Email Address')
            ->line('Click the button below to verify your email address.')
            ->line('This is your custom note: Please verify within 1 hours or you will have to register again.')
            ->action('Verify Email Address', $url)
            ->line('If you did not create an account, no further action is required.');

        });

          Schema::defaultStringLength(191);
    }
}