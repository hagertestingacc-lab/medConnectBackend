<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
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
        'cancelled'
    ) NOT NULL DEFAULT 'pending'");

        DB::statement("ALTER TABLE order_items MODIFY COLUMN sub_status ENUM(
        'pending',
        'confirmed',
        'paid',
        'processing',
        'ready',
        'shipped',
        'delivered',
        'cancelled'
    ) NOT NULL DEFAULT 'pending'");

    /*   Schema::table('order_items', function (Blueprint $table) {
        $table->enum('sub_status', [
  'pending',
        'confirmed',
        'paid',
        'processing',
        'ready',
        'shipped',
        'delivered',
        'cancelled'
                ])->default('pending')->after('final_price');
    }); */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::statement("ALTER TABLE orders MODIFY COLUMN status ENUM(
        'pending',
        'paid',
        'confirmed',
        'shipped',
        'delivered',
        'cancelled'
    ) NOT NULL DEFAULT 'pending'");
    }
};
