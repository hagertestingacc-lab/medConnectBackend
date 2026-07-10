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
       Schema::table('extend_rents', function (Blueprint $table) {
            $table->date('prev_day')->nullable();
        });    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('extend_rents', function (Blueprint $table) {
            $table->dropColumn('prev_day');
        });
    }
};
