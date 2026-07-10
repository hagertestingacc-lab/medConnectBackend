<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extend_rents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained("order_items")->cascadeOnDelete();
            $table->date('extend_day')->nullable();
            $table->string('invoice_key')->unique();
            $table->enum('status', ['pending', 'paid', 'cancelled'])->default('pending');
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('payment_method')->nullable(); // gateway or cash
            $table->timestamps();
        });

         Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('extend_day');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extend_rents');
    }
};
