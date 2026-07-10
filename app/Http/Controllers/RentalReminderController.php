<?php

namespace App\Http\Controllers;

use App\Mail\RentalReminderMail;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RentalReminderController extends Controller
{
    public function send(Request $request)
    {

         $this->authorizeCron($request);

        /* // Protect the endpoint so randoms can't trigger it
        if ($request->header('Authorization') !== 'Bearer ' . config('services.cron.secret')) {
            abort(403);
        }
 */
         $targetDate = Carbon::now()->addDays(2)->toDateString();
      
/*   $targetDate = Carbon::now()->subDays(2)->toDateString();
 */     

/* $targetDate="2026-04-24";
 *//*  */

        $orders = Order::where('order_type', 'rental')
            ->whereHas('items', function ($q) use ($targetDate) {
                $q->whereDate('rental_end', $targetDate);
            })
            ->with('doctor', 'items.product.rentalDetails')
            ->get();

       $sentCount = 0;
        $failedCount = 0;

        foreach ($orders as $order) {
            if (!$order->doctor?->allUser?->email) {
                continue;
            }
            try {
                Mail::to($order->doctor->allUser->email)->send(new RentalReminderMail($order));
                $sentCount++;
            } catch (\Throwable $e) {
                $failedCount++;
                print_r($e->getMessage());
                Log::error("Rental reminder failed for order #{$order->id}: {$e->getMessage()}");
            }
        }

        return response()->json([
            'status' => 'ok',
            'target_date' => $targetDate,
            'total_matched' => $orders->count(),
            'sent' => $sentCount,
            'failed' => $failedCount,
        ]);
    }
  private function authorizeCron(Request $request): void
{
    $secret = $request->bearerToken();

    if ($secret !== config('services.cron.secret')) {
        abort(403, 'Unauthorized');
    }
}
}