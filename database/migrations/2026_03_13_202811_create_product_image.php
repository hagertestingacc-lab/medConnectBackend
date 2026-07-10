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
Schema::create('product_image', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')
          ->constrained('product')   // references 'id' on 'products' table
          ->onDelete('cascade');

    $table->string('image', 500);
    $table->string('cloudinary_image_id', 500);
    $table->timestamps();
     // created_at, updated_at

    // Index is automatically created by foreignId()->constrained()
    // If you want an explicit index:
    // $table->index('product_id');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        schema::dropIfExists("product_image");
    }
};