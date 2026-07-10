<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
    $table->foreignId('doctor_id')->constrained("doctors")->onDelete('cascade')->onUpdate("cascade");
            $table->enum('order_type', ['sale', 'rental']);
            $table->enum('order_issue', ['None', 'Late delivery', 'wrong product', 'payment dispute', 'quality complaint'])->default('None');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('total', 10, 2);
            $table->string('invoice_key')->nullable();
            $table->string('invoice_number')->unique();
            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
