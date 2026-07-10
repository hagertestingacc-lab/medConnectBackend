<?php

namespace App\Models\ProductPart;

use App\Models\Cart;
use App\Models\Category;
use App\Models\SupplierPart\Supplier;
use App\Models\EquipmentListItem;
use App\Notifications\customNotification;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    protected $table = "product";

    protected $fillable = [
        'supplier_id',
        'name',
        'category_id',
        'price',
        'stock',
        'setup_duration',
        'description',
        'status',
        'warranty',
        'configuration',
        'specification',
        'is_rentable',
        'restock_date',
        'is_archive',
    ];
    protected $casts = [
        'price'          => 'decimal:2',
        'stock'          => 'integer',
        'setup_duration' => 'integer',
        'is_rentable'    => 'boolean',
        'is_archive'     => 'boolean',
        'specification'  => 'array',
        'restock_date'   => 'date',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
    ];

        protected static function booted()
    {
        static::retrieved(function (Product $product) {
            if ($product->restock_date && $product->restock_date->isPast()) {
                $product->restock_date = null;
                $product->saveQuietly();
            }
        });
    }
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /**
     * Get the category that the product belongs to.
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
    public function image()
    {
        return $this->hasMany(ProductImage::class, 'product_id');
    }
    public function rentalDetails()
    {
        return $this->hasOne(ProductRentalDetails::class, 'product_id');
    }

    public function cart()
    {
        return $this->hasOne(Cart::class, 'product_id');
    }

    public function orderItems()
    {
        return $this->hasMany(\App\Models\OrderItem::class, 'product_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'product_id');
    }

    public function validateRentalDuration(string $startDate, string $endDate): ?string
    {
        if (! $this->rentalDetails) {
            return 'Rental details are missing for this product.';
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        if ($end->lt($start)) {
            return 'Rental end date must be after or equal to rental start date.';
        }

        $days = $start->diffInDays($end) + 1;

        if ($this->rentalDetails->minimum_rental_days && $days < $this->rentalDetails->minimum_rental_days) {
            return 'The rental period must be at least ' . $this->rentalDetails->minimum_rental_days . ' day(s).';
        }

        if ($this->rentalDetails->maximum_rental_days && $days > $this->rentalDetails->maximum_rental_days) {
            return 'The rental period may not exceed ' . $this->rentalDetails->maximum_rental_days . ' day(s).';
        }

        return null;
    }

    public function validateRent(array $data): ?string
    {
        if (! $this->is_rentable) {
            return 'not rentable';
        }

        if (! $this->rentalDetails) {
            return 'Rental details are missing for this product.';
        }

        if (isset($data['quantity']) && $data['quantity'] > $this->rentalDetails->available_units) {
            return 'Insufficient stock for rent';
        }

        if (empty($data['rental_start_date']) || empty($data['rental_end_date'])) {
            return 'Rental start date and rental end date are required.';
        }

        return $this->validateRentalDuration($data['rental_start_date'], $data['rental_end_date']);
    }

    /**
     * Scope a query to only include products with a specific status.
     */
    public function scopeWhereStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', "create_accepted")->orWhere('status', "edit_accepted");
    }
    /**
     * Scope a query to only include rentable products.
     */
    public function scopeRentable($query)
    {
        return $query->where('is_rentable', true);
    }

    /**
     * Scope a query to exclude archived products.
     */
    public function scopeNotArchived($query)
    {
        return $query->where('is_archive', false);
    }

    public function restockNotifications()
    {
        return $this->hasMany(\App\Models\RestockNotification::class, 'product_id');
    }

    // Product model

    public function scopeOutOfStock($query)
    {
        return $query->where('is_archive', false)
            ->where('stock', 0)
            ->whereNull('restock_date')
            ->where(function ($q) {
                $q->where('is_rentable', false)
                    ->orWhereHas('rentalDetails', function ($rq) {
                        $rq->where('stock_units', 0);
                    });
            })
            ->with('rentalDetails');
    }

    public static function archiveOutOfStockProducts(): int
    {
        $count = 0;

        foreach (static::outOfStock()->get() as $product) {
            if ($product->archiveIfOutOfStock()) {
                $count++;
            }
        }

        return $count;
    }

    protected function archiveIfOutOfStock(): bool
    {
        $archived = DB::transaction(function () {
            $product = static::where('id', $this->id)->lockForUpdate()->first();

            if (!$product || $product->is_archive) {
                return false;
            }

            $rentalOk = !$product->is_rentable
                || ($product->rentalDetails->stock_units ?? 0) == 0;

            if ($product->stock != 0 || $product->restock_date !== null || !$rentalOk) {
                return false;
            }

            $product->is_archive = true;
            return $product->save();
        });

        if ($archived) {
            $this->notifySupplierArchived();
        }

        return $archived;
    }

    protected function notifySupplierArchived(): void
    {
        if (!$this->supplier) {
            return;
        }

        try {
            $this->supplier->allUser->notify(new customNotification(
                "Your product '{$this->name}' (#{$this->id}) has been automatically archived because it is out of stock."
            ));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
