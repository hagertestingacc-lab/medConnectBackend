<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Log;

class AuthTest extends TestCase
{
    /**
     * Test successful doctor registration
     */
    public function test_successful_doctor_registration()
    {
        $validData = [
            'full_name' => 'دكتورة هبة عماد الدين',
            'email' => 'unddnsdnskdnaskldnaskd@gmail.com', // Unique email
            'password' => 'P@ssword123',
            'address' => 'zagazig',
            'governorate' => 'elsharqia',
            'national_id' => '28809181234570',
            'phone' => '01012345678',
            'license_number' => 'LIC-2022-004-CAI'
        ];

        Log::info('Testing successful registration with data:', $validData);

        $response = $this->postJson('/api/v1/doctor/register', $validData);

        Log::info('Registration response status:', ['status' => $response->status()]);
        Log::info('Registration response body:', $response->json());

        $response->assertStatus(201);
    }

    /**
     * Test validation error - missing full_name
     */
    public function test_registration_missing_full_name()
    {
        $invalidData = [
            'email' => 'test@example.com',
            'password' => 'SecurePassword123',
            'address' => '123 Medical Street',
            'governorate' => 'Cairo',
            'national_id' => '12345678901234',
            'phone' => '01012345678',
            'license_number' => 'LIC123'
        ];

        Log::warning('Testing registration without full_name');

        $response = $this->postJson('/api/v1/doctor/register', $invalidData);

        Log::error('Validation error response:', [
            'status' => $response->status(),
            'errors' => $response->json()
        ]);

        $response->assertStatus(422);
        $this->assertArrayHasKey('full_name', $response->json()['errors']);
    }

    /**
     * Test validation error - invalid email
     */
    public function test_registration_invalid_email()
    {
        $invalidData = [
            'full_name' => 'Dr. Test',
            'email' => 'invalid-email', // Invalid email format
            'password' => 'SecurePassword123',
            'address' => '123 Medical Street',
            'governorate' => 'Cairo',
            'national_id' => '12345678901234',
            'phone' => '01012345678',
            'license_number' => 'LIC123'
        ];

        Log::warning('Testing registration with invalid email');

        $response = $this->postJson('/api/v1/doctor/register', $invalidData);

        Log::error('Email validation error:', $response->json());

        $response->assertStatus(422);
        $this->assertArrayHasKey('email', $response->json()['errors']);
    }

    /**
     * Test validation error - password too short
     */
    public function test_registration_password_too_short()
    {
        $invalidData = [
            'full_name' => 'Dr. Test',
            'email' => 'test' . time() . '@example.com',
            'password' => 'short', // Less than 8 characters
            'address' => '123 Medical Street',
            'governorate' => 'Cairo',
            'national_id' => '12345678901234',
            'phone' => '01012345678',
            'license_number' => 'LIC123'
        ];

        Log::warning('Testing registration with short password');

        $response = $this->postJson('/api/v1/doctor/register', $invalidData);

        Log::error('Password validation error:', $response->json());

        $response->assertStatus(422);
        $this->assertArrayHasKey('password', $response->json()['errors']);
    }

    /**
     * Test validation error - invalid national ID (not 14 digits)
     */
    public function test_registration_invalid_national_id()
    {
        $invalidData = [
            'full_name' => 'Dr. Test',
            'email' => 'test' . time() . '@example.com',
            'password' => 'SecurePassword123',
            'address' => '123 Medical Street',
            'governorate' => 'Cairo',
            'national_id' => '1234567', // Too short
            'phone' => '01012345678',
            'license_number' => 'LIC123'
        ];

        Log::warning('Testing registration with invalid national ID');

        $response = $this->postJson('/api/v1/doctor/register', $invalidData);

        Log::error('National ID validation error:', $response->json());

        $response->assertStatus(422);
        $this->assertArrayHasKey('national_id', $response->json()['errors']);
    }

    /**
     * Test validation error - invalid phone (not 11 digits)
     */
    public function test_registration_invalid_phone()
    {
        $invalidData = [
            'full_name' => 'Dr. Test',
            'email' => 'test' . time() . '@example.com',
            'password' => 'SecurePassword123',
            'address' => '123 Medical Street',
            'governorate' => 'Cairo',
            'national_id' => '12345678901234',
            'phone' => '123456', // Too short
            'license_number' => 'LIC123'
        ];

        Log::warning('Testing registration with invalid phone');

        $response = $this->postJson('/api/v1/doctor/register', $invalidData);

        Log::error('Phone validation error:', $response->json());

        $response->assertStatus(422);
        $this->assertArrayHasKey('phone', $response->json()['errors']);
    }

    /**
     * Test validation error - duplicate email
     */
    public function test_registration_duplicate_email()
    {
        $email = 'duplicate' . time() . '@test.com';

        // First registration
        $firstData = [
            'full_name' => 'Dr. First',
            'email' => $email,
            'password' => 'SecurePassword123',
            'address' => '123 Medical Street',
            'governorate' => 'Cairo',
            'national_id' => '12345678901234',
            'phone' => '01012345678',
            'license_number' => 'LIC123'
        ];

        $this->postJson('/api/v1/doctor/register', $firstData);

        // Second registration with same email
        $secondData = [
            'full_name' => 'Dr. Second',
            'email' => $email, // Same email
            'password' => 'SecurePassword123',
            'address' => '456 Hospital Ave',
            'governorate' => 'Alexandria',
            'national_id' => '98765432109876',
            'phone' => '01098765432',
            'license_number' => 'LIC456'
        ];

        Log::warning('Testing registration with duplicate email');

        $response = $this->postJson('/api/v1/doctor/register', $secondData);

        Log::error('Duplicate email error:', $response->json());

        $response->assertStatus(422);
        $this->assertArrayHasKey('email', $response->json()['errors']);
    }
}
