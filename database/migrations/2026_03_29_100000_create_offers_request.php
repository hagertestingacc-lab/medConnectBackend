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
        Schema::create('offerRequest', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('customRequest')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('supplier_id')->constrained('supplier')->onDelete('cascade')->onUpdate('cascade');
            $table->decimal('price', 10, 2);
            $table->integer('delivery_days');
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->timestamps();

            $table->unique(['supplier_id', 'request_id'], 'unique_supplier_request');
            $table->index('request_id');
            $table->index('supplier_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offerRequest');
    }
};