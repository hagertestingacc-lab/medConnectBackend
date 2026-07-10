<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cart', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('product_id')->constrained('product')->onDelete('cascade')->onUpdate('cascade');
            $table->integer('quantity')->default(1);
            $table->enum('type', ['sale', 'rental'])->default('sale');
            $table->date('rental_start_date')->nullable();
            $table->date('rental_end_date')->nullable();
            $table->timestamps();

            $table->unique(['doctor_id', 'product_id', 'type', 'rental_start_date', 'rental_end_date'], 'unique_cart_item');
            $table->index('doctor_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart');
    }
};