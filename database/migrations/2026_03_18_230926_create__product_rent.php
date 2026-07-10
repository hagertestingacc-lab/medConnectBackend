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
          Schema::create('productRentalDetails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained("product")->unique()->onDelete("cascade"); // one-to-one
            $table->decimal('price_daily', 10, 2);               // NOT NULL in DB
            $table->integer('minimum_rental_days')->default(1);  // NOT NULL with default
            $table->integer('maximum_rental_days')->default(365);// NOT NULL with default
            $table->integer('available_units');      // nullable
            $table->string('preparation_duration')->default("1 min"); // nullable
            $table->timestamps(); // optional – not in your original, but often useful


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_rental_details');
    }
};
