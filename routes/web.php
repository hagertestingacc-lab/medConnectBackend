<?php

use App\Http\Controllers\RentalReminderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/api/cron/rental-reminders', [RentalReminderController::class, 'send']);// routes/api.php or web.php
Route::get('/api/cron/archive-out-of-stock', function (Request $request) {
    abort_unless($request->header('Authorization') === 'Bearer ' . config('services.cron.secret'), 403);

    Artisan::call('products:archive-out-of-stock');

    return response()->json(['output' => Artisan::output()]);
});