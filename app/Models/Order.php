<?php

namespace App\Models;

use App\Models\DoctorPart\Doctor;
use App\Models\ProductPart\Product;
use App\Notifications\customNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class Order extends Model
{
    protected $table = 'orders';

    protected $fillable = [
        'order_number',
        'doctor_id',
        'order_type',
        'order_issue',
        'subtotal',
        'discount_amount',
        'total',
        'invoice_key',
        'invoice_number',
        'status',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public static function makeCartOrder(Doctor $doctor, array $data)
    {

        $items = $doctor->cart->all();

        $total = $doctor->cart->sum(function ($item) {

            return $item->product->price * $item->quantity;
        });


        return DB::transaction(function () use ($doctor, $items, $total, $data) {
            $payment_type = $data["payment_type"] == "cash" ? "confirmed" : "pending";

            $order = self::create([
                'doctor_id' => $doctor->id,
                'invoice_number' => "INV-" . time(),
                'order_type' => "sale",
                'subtotal' => $total,
                'total' => $total,
                "status" =>  $payment_type
            ]);

            foreach ($items as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item->product->price,
                    'final_price' => $item->product->price * $item['quantity'],
                    'rental_start' => $item['rental_start'] ?? null,
                    'rental_end' => $item['rental_end'] ?? null,
                    "sub_status" => $payment_type
                ]);

                // Decrease stock for the ordered product
                $item->product->decrement('stock', $item['quantity']);

                // Check if stock became 0 and update equipment list items
                if ($item->product->stock == 0) {
                    EquipmentListItem::where('product_id', $item->product->id)->update(['is_ava' => false]);
                }
            }

            $doctor->cart()->delete();
            return ["success" => true, "data" => $order->load('items.product')];
        });
    }
    public static function makeRentalOrder(Doctor $doctor, array $data)
    {
        $product = Product::where("id", $data['product_id'])->first();

        if (!$product)
            return ["success" => false, "error" => "product not found"];

        $validateDate = $product->validateRent($data);

        if ($validateDate !== null)
            return ["success" => false, "error" => $validateDate];


        $start = Carbon::parse($data['rental_start_date'])->startOfDay();
        $end = Carbon::parse($data['rental_end_date'])->startOfDay();

        $days = $start->diffInDays($end);
        return DB::transaction(function () use ($doctor, $data, $product, $days) {
            $payment_type = $data["payment_type"] == "cash" ? "confirmed" : "pending";



            $order = self::create([
                'doctor_id' => $doctor->id,
                'invoice_number' => "INV-" . time(),
                'order_type' => "rental",
                'subtotal' => $product->rentalDetails->price_daily * $data['quantity'] * $days,
                'total' =>  $product->rentalDetails->price_daily * $data['quantity'] * $days,
                "status" => $payment_type

            ]);
            $order->items()->create([
                'product_id' =>  $product->id,
                'quantity' => $data['quantity'],
                'unit_price' => $product->rentalDetails->price_daily,
                'final_price' => $product->rentalDetails->price_daily * $data['quantity'] * $days,
                'rental_start' => $data['rental_start_date'],
                'rental_end' => $data['rental_end_date'],
                'sub_status' => 'confirmed'
            ]);
            $product->rentalDetails()->decrement('available_units', $data['quantity']);


            return [
                "success" => true,
                "data" => $order->load('items.product.rentalDetails'),
                "days" => $days ?? 1
            ];
        });
    }

    public static function generateOrderNumber(): string
    {
        $count = self::whereYear('created_at', date('Y'))->count() + 1;
        return sprintf('ORD-%s-%04d', date('Y'), $count);
    }

    public function cancelIfPending(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        return $this->cancelAndRestock();
    }

    public function cancelIfConfirmed(): bool
    {
        if ($this->status !== 'confirmed') {
            return false;
        }

        return $this->cancelAndRestock();
    }

   protected function cancelAndRestock(): bool
    {
        $result = DB::transaction(function () {
            $items = $this->items()->lockForUpdate()->get();

            if ($this->order_type == "sale") {
                foreach ($items as $item) {
                    $item->product->lockForUpdate()->increment('stock', $item->quantity);
                    $item->update(['sub_status' => 'cancelled']);
                }
            }

            if ($this->order_type == "rental") {
                foreach ($items as $item) {
                    $item->product->rentalDetails->lockForUpdate()->increment('available_units', $item->quantity);
                    $item->update(['sub_status' => 'cancelled']);
                }
            }

            $this->status = 'cancelled';
            return $this->save();
        });

        if ($result) {
            $this->notifyCancellation();
        }

        return $result;
    }

    protected function notifyCancellation(): void
    {
        if (!$this->doctor) {
            return;
        }

        try {
            $this->doctor->allUser->notify(new customNotification(
                "Your order #{$this->id} has been automatically cancelled because payment wasn't received within 15 minutes. Any reserved items have been released back to stock."
            ));
        } catch (\Throwable $e) {
            // don't let a mail failure roll back or break the cancellation flow
            report($e);
        }
    }

    public function assignIssue(string $issue): self
    {
        $this->order_issue = $issue;
        $this->save();

        return $this;
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function extendRentalDays(array $data)
    {
        // Check if order is paid and is rental type
        /*  if ($this->status !== 'delivered' || $this->order_type !== 'rental') {
            return [
                "success" => false,
                "error" => "Order must be delivered and rental type to extend"
            ];
        } */

        $item = $this->items()->first();
        if (!$item || !$item->product->is_rentable) {
            return [
                "success" => false,
                "error" => "Product is not rentable"
            ];
        }

        if (!$item) {
            return ['success' => false, 'error' => 'Item not found'];
        }

        // Block a second extension if one is already pending or already paid
        $existingExtension = $item->extendRent;

        if ($existingExtension && in_array($existingExtension->status, ['pending', 'paid'])) {
            return [
                'success' => false,
                'error' => $existingExtension->status === 'paid'
                    ? 'This item has already been extended.'
                    : 'An extension payment is already pending for this item.',
            ];
        }

        $rentalDetails = $item->product->rentalDetails;
        $currentRentalDays = Carbon::parse($item->rental_end)->diffInDays(Carbon::parse($item->rental_start));
        $maxDays = $rentalDetails->maximum_rental_days;
        $remainingDays = $maxDays - $currentRentalDays;

        if ($remainingDays <= 0) {
            return [
                "success" => false,
                "error" => "Maximum rental days reached. Cannot extend further."
            ];
        }

        // Validate extension days
        $extensionDays = $data['extension_days'] ?? 0;
        if ($extensionDays <= 0 || $extensionDays > $remainingDays) {
            return [
                "success" => false,
                "error" => "Invalid extension days. Maximum remaining days: {$remainingDays}"
            ];
        }

        // Calculate new rental end date and total price
        $newRentalEnd = Carbon::parse($item->rental_end)->addDays($extensionDays);
        $extensionPrice = $rentalDetails->price_daily * $item->quantity * $extensionDays;

        return [
            "success" => true,
            "extension_days" => $extensionDays,
            "new_rental_end" => $newRentalEnd->format('Y-m-d'),
            "extension_price" => $extensionPrice,
            "item" => $item
        ];
    }


    public function confirmExtension(int $itemId, ExtendRent $extendRent): bool
    {
        $item = $this->items()->find($itemId);
        if (!$item) {
            return false;
        }

        $oldRentalEnd = $item->rental_end
            ? Carbon::parse($item->rental_end)
            : null;



        $newRentalEnd = Carbon::parse($extendRent->extend_day);

        DB::transaction(function () use ($item, $newRentalEnd, $oldRentalEnd) {
            $item->update([
                'rental_end' => $newRentalEnd,
            ]);

            $item->extendRent->update(
                ["prev_day" => $oldRentalEnd]
            );
        });

        $extensionDays = $oldRentalEnd
            ? $oldRentalEnd->diffInDays($newRentalEnd)
            : null;

        $this->sendExtensionEmails($item, $extensionDays, $newRentalEnd);

        return true;
    }



private function sendExtensionEmails(OrderItem $item, ?int $extensionDays, Carbon $newRentalEnd): void
{
    try {
        $supplier = $item->product->supplier;
        $doctor = $this->doctor;

        $supplier->allUser->notify(new customNotification(
            "Doctor {$doctor->allUser->fullname} has extended rental for {$item->product->name} by {$extensionDays} days. New rental end date: {$newRentalEnd->format('Y-m-d')}."
        ));

        $doctor->allUser->notify(new customNotification(
            "Your rental extension for {$item->product->name} has been confirmed. New rental end date: {$newRentalEnd->format('Y-m-d')}."
        ));
    } catch (\Throwable $e) {
        Log::error('Extension confirmation emails failed', [
            'item_id' => $item->id,
            'error'   => $e->getMessage(),
        ]);
    }
}


    // Order.php
      public function scopeExpiredPending($query)
    {
        return $query->where('status', 'pending')
            ->where('created_at', '<=', now()->subMinutes(15));
    }

    public static function cancelExpiredPendingOrders(): int
    {
        $orders = static::expiredPending()->lockForUpdate()->get();
        $count = 0;
        foreach ($orders as $order) {
            // re-check status in case another process already cancelled it
            if ($order->status === 'pending' && $order->cancelAndRestock()) {
                $count++;
            }
        }

        return $count;
    }
    




// Order model

public function scopeWithExpiredRentalPending($query)
{
    return $query->whereHas('items.extendRent', function ($q) {
        $q->where('status', 'pending')
          ->where('created_at', '<=', now()->subDay(1));
    })->with(['items.extendRent']);
}

public static function cancelExpiredRentalPending(): int
{
    $orders = static::withExpiredRentalPending()->get();
    $count = 0;

    foreach ($orders as $order) {
        foreach ($order->items as $item) {
            $extendRent = $item->extendRent;

            if (!$extendRent
                || $extendRent->status !== 'pending'
                || $extendRent->created_at > now()->subDay()) {
                continue;
            }

            if ($order->cancelExtendRentalForItem($item)) {
                $count++;
            }
        }
    }

    return $count;
}

// still on Order, but now takes the specific item
protected function cancelExtendRentalForItem(OrderItem $item): bool
{
    $cancelled = DB::transaction(function () use ($item) {
        // lock the specific extend_rents row, not the order
        $extendRent = $item->extendRent()->lockForUpdate()->first();

        if (!$extendRent || $extendRent->status !== 'pending') {
            return false;
        }

        $extendRent->status = 'cancelled';
        return $extendRent->save();
    });

    if ($cancelled) {
        $this->notifyRentalCancellation($item);
    }

    return $cancelled;
}

protected function notifyRentalCancellation(OrderItem $item): void
{
    if (!$this->doctor) {
        return;
    }

    try {
        $this->doctor->allUser->notify(new customNotification(
            "Your Extend rental for order #{$this->id}, item #{$item->id} has been automatically cancelled because payment wasn't received within 1 day."
        ));
    } catch (\Throwable $e) {
        report($e);
    }
}




    /**
     * Work out what the order status SHOULD be, based on each item's sub_status.
     * Doesn't save — call refreshStatus() for that.
     */
   public function computeStatus(): string
{
    $statuses = $this->items->pluck('sub_status');
    $total = $statuses->count();

    if ($total === 0) {
        return $this->status;
    }

    $cancelledCount = $statuses->filter(fn ($s) => $s === 'cancelled')->count();

    if ($cancelledCount === $total) {
        return 'cancelled';
    }

    $active = $statuses->reject(fn ($s) => $s === 'cancelled');

    $rank = [
        'pending'    => 0,
        'confirmed'  => 1,
        'paid'       => 2,
        'processing' => 3,
        'ready'      => 4,
        'shipped'    => 5,
        'delivered'  => 6,
        'returned'   => 7,
    ];

    $base = $active->sortByDesc(fn ($s) => $rank[$s] ?? 0)->first();

    $partialable = ['processing', 'ready', 'shipped', 'delivered', 'returned'];

    $isPartial = ($active->unique()->count() > 1 || $cancelledCount > 0)
        && in_array($base, $partialable, true);

    return $isPartial ? "partial_{$base}" : $base;
}

    /**
     * Recompute status from items and save if it changed.
     * Returns true if the status actually changed.
     */
    public function refreshStatus(): bool
    {
        $this->load('items');
        $newStatus = $this->computeStatus();

        if ($this->status !== $newStatus) {
            $this->status = $newStatus;
            $this->save();
            return true;
        }

        return false;
    }
}