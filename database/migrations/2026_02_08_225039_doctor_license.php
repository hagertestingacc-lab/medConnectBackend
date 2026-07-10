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
       Schema::create('doctor_licenses', function (Blueprint $table) {
    $table->string('license_number', 50)->primary();
    $table->string('full_name', 120);
    $table->string('national_id', 14)->unique();
    $table->string('specialty', 100);
    $table->enum('issue_authority', [
        'النقابة العامة للأطباء المصريين',
        'وزارة الصحة والسكان',
        'وزارة الصحة - الإدارة العامة للتراخيص',
        'المستشفيات الجامعية',
        'غير محدد'
    ])->default('غير محدد');

    $table->enum('authority_type', ['نقابة', 'حكومي', 'عسكري', 'جامعي', 'خاص'])->nullable();
    $table->string('syndicate_number', 50)->nullable();
    $table->string('syndicate_branch', 50)->nullable();
    $table->date('issue_date');
    $table->date('expiry_date');
 $table->enum('license_status', ['active', 'expired'])
      ->default('active');
    $table->string('governorate', 50)->nullable();
    $table->string('city', 50)->nullable();
    $table->string('workplace', 255)->nullable();
    $table->enum('workplace_type', ['مستشفى عام', 'مستشفى خاص', 'عيادة', 'مركز طبي', 'وحدة'])->default('مستشفى عام');
    $table->enum('license_level',
    ['استشاري', 'أخصائي', 'ممارس عام', 'طبيب امتياز', 'طبيب مقيم'])->nullable();
    $table->date('last_updated');

    $table->index('license_status');

    $table->index('issue_authority');
    $table->index('license_level');
    $table->index('national_id');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
/*        schema::dropIfExists("doctor_licenses");
 */    }
};
