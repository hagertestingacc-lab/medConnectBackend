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
        Schema::create('
        ', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_table_id')->unique()->constrained("user","id")->onDelete('cascade')->onUpdate("cascade");
    $table->string('license_table_id',50)->unique();
    $table->foreign("license_table_id")->references('license_number')->on('doctor_licenses');
    $table->string('phone', 11)->nullable();
    $table->string('profile_image_url', 500);
    $table->string('cloudinary_profile_img_id', 255);

    $table->boolean('is_verified')->default(false);
    $table->timestamps();

    // Indexes      z
    $table->index('user_table_id');
    $table->index('license_table_id');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
/*         Schema::dropIfExists('doctors');
 */    }
};