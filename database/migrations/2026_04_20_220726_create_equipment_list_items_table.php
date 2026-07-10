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
        Schema::create('equipment_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('list_id')->constrained('equipment_lists')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('product');
            $table->boolean('is_ava')->default(true);
            $table->timestamp('added_at')->useCurrent();
            $table->unique(['list_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_list_items');
    }
};
