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
/*         Schema::create('doctor_addresses', function (Blueprint $table) {
    $table->id();
    $table->foreignId('doctor_id')->unique()->constrained("doctors")->cascadeOnDelete()->cascadeOnUpdate();
    $table->string('address', 255);
    $table->string('governorate', 100);
    $table->timestamps();
}); */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        schema::dropIfExists("doctor_addresses");
    }
};
