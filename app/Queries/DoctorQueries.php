<?php
namespace App\Queries;
use App\Models\AllUserPart\AllUser;
use App\Models\DoctorPart\DoctorLicense;
use App\Models\DoctorPart\Doctor;
use App\Models\DoctorPart\DoctorAddresses;
use App\Enums\userRole;
use App\Enums\userStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class DoctorQueries {

public static function create(array $validated, object|array $license, array $uploadImage): array
{
    try {
        $result = DB::transaction(function () use ($validated, $license, $uploadImage) {
            $allUser = self::createUser($validated);
            $doctor = self::createDoctorUser($validated, $allUser->id, $license, $uploadImage);
/*             self::createDoctorAddress($validated, $doctor->id);
 */
            return [
                'success' => true,
                'message' => "Doctor created successfully",
                'data' => [
                "doctor"=> $doctor,
                "allUser"=>$allUser
                ]
            ];
        });

        return $result;
    } catch (\Exception $e) {
        Log::error('Doctor creation failed: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => 'User signup failed. Please try again.'.$e->getMessage(),
            'status' => 500
        ];
    }
}

private static function createUser(array $validated)
{
        $allUser = AllUser::create([
            'role' => 'doctor',
            'status' => 'pending',
            'fullname' => $validated['full_name'],
            'password' => Hash::make($validated['password']),
            'address'=>$validated["address"],
            'email' => $validated['email'],
            'email_verified_at' => null
        ]);
        return $allUser;

}
private static function createDoctorUser(array $validated, int $allUserId, object|array $license, array $uploadImage)
{
    $doctor = Doctor::create([
        'user_table_id' => $allUserId,
        'license_table_id' =>  $license['license_number'],
        'phone' => $validated['phone'],
        'profile_image_url' =>$uploadImage['url'],
        'cloudinary_profile_img_id' => $uploadImage['public_id'],
        'is_verified' => false
    ]);
    return $doctor;

}
/* private static function createDoctorAddress(array $validated, int $doctorId): void
{
    DoctorAddresses::create([
        'doctor_id' => $doctorId,
        'address' => $validated['address'],
        'governorate' => $validated['governorate']
    ]);
}
 */

}
