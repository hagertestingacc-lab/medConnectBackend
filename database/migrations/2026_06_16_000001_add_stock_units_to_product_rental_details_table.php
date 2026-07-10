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
        Schema::table('productRentalDetails', function (Blueprint $table) {
            $table->integer('stock_units')->default(0)->after('available_units');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productRentalDetails', function (Blueprint $table) {
            $table->dropColumn('stock_units');
        });
    }
};
