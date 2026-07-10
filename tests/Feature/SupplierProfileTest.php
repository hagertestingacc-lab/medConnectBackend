<?php

namespace Tests\Feature;

use App\Models\AllUserPart\AllUser;
use App\Models\SupplierPart\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SupplierProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_can_view_own_profile(): void
    {
        $user = AllUser::create([
            'role' => 'supplier',
            'status' => 'active',
            'fullname' => 'Jane Supplier',
            'email' => 'supplier@example.com',
            'password' => bcrypt('password123'),
            'address' => 'Cairo, Egypt',
        ]);

        Supplier::create([
            'user_table_id' => $user->id,
            'national_id' => '1234567890123',
            'phone' => '01000000000',
            'company_image_url' => 'https://example.com/company.jpg',
            'cloudinary_company_image_id' => 'company-id',
            'company_name' => 'Med Supplies',
            'governorate' => 'Cairo',
            'tax_card_image' => 'https://example.com/tax.jpg',
            'cloudinary_tax_card_id' => 'tax-id',
            'certificate_image' => 'https://example.com/cert.jpg',
            'cloudinary_certificate_id' => 'cert-id',
            'certificate_name' => 'ISO',
            'is_verified' => true,
        ]);

        Sanctum::actingAs($user, ['supplier']);

        $response = $this->getJson('/api/v1/supplier/profile');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.fullname', 'Jane Supplier')
            ->assertJsonPath('data.company_name', 'Med Supplies');
    }
}
