<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
         $table->foreignId('order_id')->constrained("orders")->onDelete('cascade')->onUpdate("cascade");
         $table->foreignId('product_id')->constrained("product")->onDelete('cascade')->onUpdate("cascade");

            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('final_price', 10, 2);
            $table->date('rental_start')->nullable();
            $table->date('rental_end')->nullable();
            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
