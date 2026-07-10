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
        Schema::create('supplier', function (Blueprint $table) {
            $table->id();
            $table->foreignId("user_table_id")->unique()->constrained("user")->onDelete('cascade')->onUpdate("cascade");
            $table->timestamps();
            $table->string("national_id",14)->unique();

            // Fields exactly as provided
            $table->string('phone', 20); // NOT NULL handled by ->nullable(false) which is default
            $table->string('company_image_url', 500);
            $table->string('cloudinary_company_image_id', 255);
            $table->string('company_name', 200);
            $table->string('governorate', 50);
            $table->string('tax_card_image', 500);
            $table->string('cloudinary_tax_card_id', 255);
            $table->string('certificate_image', 500);
            $table->string('cloudinary_certificate_id', 255);
            $table->string('certificate_name', 500);



            $table->boolean('is_verified')->default(false);


            //



            // Index as requested
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::dropIfExists('supplier');
    }
};
