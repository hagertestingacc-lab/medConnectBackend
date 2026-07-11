<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
            'partial_processing',
            'partial_ready',
            'partial_shipped',
            'partial_delivered'
        ) NOT NULL DEFAULT 'pending'");

        Schema::table('order_items', function (Blueprint $table) {
            $table->text('cancel_reason')->nullable()->after('sub_status');
            $table->timestamp('cancelled_at')->nullable()->after('cancel_reason');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['cancel_reason', 'cancelled_at']);
        });

        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM(
            'pending',
            'confirmed',
            'paid',
            'processing',
            'ready',
            'shipped',
            'delivered',
            'cancelled'
            'returned'
        ) NOT NULL DEFAULT 'pending'");
    }
};