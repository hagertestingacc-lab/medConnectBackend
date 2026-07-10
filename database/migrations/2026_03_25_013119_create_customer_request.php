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
         Schema::create('customRequest', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained("doctors")->onDelete('cascade');
            $table->string('additionalDetails')->nullable();
            $table->decimal('budget', 10, 2)->nullable();
            $table->json('item');
            $table->enum("type",["rental","tools","paid devices"]);
            $table->date('expires_at');
            $table->date('rent_start_date')->nullable();
            $table->date('rent_end_date')->nullable();
            $table->enum('status', ['open', 'delivered', 'in negotiation', 'shipped', 'expired', 'cancelled'])
                  ->default('open');
            $table->timestamps();

            // Index
            $table->index(['status', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_request');
    }
};