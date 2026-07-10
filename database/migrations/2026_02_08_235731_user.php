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
  Schema::create('user', function (Blueprint $table) {
    $table->id();
    $table->enum('role', ['doctor', 'supplier', 'admin']);
    $table->string('fullname', 120);
    $table->string('email', 100)->unique();
    $table->string('password', 255);
    $table->string('address', 255);
    $table->enum('status', ['active', 'inactive', 'suspended', 'pending'])->default('pending');
    $table->timestamp('email_verified_at')->nullable();
    $table->timestamps();

    // Indexes
    $table->index('email');                     // Faster login queries
    $table->index('status');                    // Faster admin queries
    $table->index(['role', 'status']);          // Composite index for common queries
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
/*         Schema::dropIfExists('user');
 */    }
};
