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
       Schema::create('product', function (Blueprint $table) {
            $table->id();
          $table->foreignId("supplier_id")->constrained("supplier")->onDelete('cascade')->onUpdate("cascade");
            $table->string('name', 255);
            $table->foreignId("category_id")->constrained("category")->onDelete('cascade')->onUpdate("cascade");
            $table->decimal('price', 10, 2);
            $table->integer('stock');
            $table->string('setup_duration');
            $table->text('description');
            $table->enum('status', [
                'create_pending',
                'create_accepted',
                'create_rejected',
                'edit_pending',
                'edit_accepted',
                'edit_rejected'
            ])->default('create_pending');
            $table->text('warranty')->nullable();
            $table->text('configuration')->nullable();
            $table->json('specification')->nullable(); // using JSON for array data
            $table->boolean('is_rentable')->default(false);
            $table->date('restock_date')->nullable();
            $table->boolean('is_archive')->default(false);
            $table->timestamps();


            // Optional: Add indexes for common queries
            $table->index('supplier_id');
            $table->index('category_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product');
    }
};