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
        Schema::create('conversations', function (Blueprint $table) {
        $table->id();
        $table->foreignId('participant_one')->constrained('user')->onDelete('cascade');
        $table->foreignId('participant_two')->constrained('user')->onDelete('cascade');
        $table->timestamp('last_message_at')->nullable();
        $table->timestamps();

        // Prevent duplicate conversations between same two users
        $table->unique(['participant_one', 'participant_two']);
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};