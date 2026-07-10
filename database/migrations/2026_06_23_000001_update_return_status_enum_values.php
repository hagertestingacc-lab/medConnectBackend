<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM(
            'pending',
            'confirmed',
            'paid',
            'processing',
            'ready',
            'shipped',
            'delivered',
            'cancelled',
            'returned',
            'unReturned'
        ) NOT NULL DEFAULT 'pending'");

        DB::statement("ALTER TABLE order_items MODIFY COLUMN sub_status ENUM(
            'pending',
            'confirmed',
            'paid',
            'processing',
            'ready',
            'shipped',
            'delivered',
            'cancelled',
            'returned',
            'unReturned'
        ) NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE order_items MODIFY COLUMN sub_status ENUM(
            'pending',
            'confirmed',
            'paid',
            'processing',
            'ready',
            'shipped',
            'delivered',
            'cancelled',
            'returned',
            'unreturned'
        ) NOT NULL DEFAULT 'pending'");

        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM(
            'pending',
            'confirmed',
            'paid',
            'processing',
            'ready',
            'shipped',
            'delivered',
            'cancelled',
            'returned',
            'unreturned'
        ) NOT NULL DEFAULT 'pending'");
    }
};
