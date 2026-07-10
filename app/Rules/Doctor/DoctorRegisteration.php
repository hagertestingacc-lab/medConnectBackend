<?php
namespace App\Rules\Doctor;

use App\Exceptions\CustomExceptions;
use App\Http\Requests\doctorDataRequest;
use App\Queries\DoctorQueries;
use App\Services\Doctor\DoctorActivities;
use App\Services\Doctor\DoctorLicenseChecker;
use Illuminate\Support\Facades\Log;


class DoctorRegisteration
{

public function register(array $validated, doctorDataRequest $request): array
{
    try {
        $license = $this->verifyLicense($validated);
        $uploadImage = $this->handleProfileImg($request);
        $doctor = $this->createDoctor($validated, $license, $uploadImage);

        return $doctor;
    } catch (\Exception $e) {
        Log::error('Doctor registration failed: ' . $e->getMessage());
        throw CustomExceptions::globalException($e->getMessage(),$e->getCode()??500);
    }
}

private static function verifyLicense(array $validated): array
{
    $licenseChecker = new DoctorLicenseChecker();
    $license = $licenseChecker->check(
        $validated['license_number'],
        $validated['national_id'],
        $validated['full_name']
    );

    if (!$license['success']) {
        $errorMessage = $license['message'] ?? $license['error'] ?? 'License verification failed';
        throw CustomExceptions::globalException($errorMessage,$license['status'] ?? 500);
    }

    return $license;
}


private function handleProfileImg(doctorDataRequest $request): array
{
    if (!$request->hasFile('profile_image_url')) {
        return $this->getDefaultProfileImg();
    }

    $file = $request->file('profile_image_url');

    if (!$file->isValid()) {
        throw CustomExceptions::globalException('Failed to upload: invalid image');
    }

    $uploadedImage = DoctorActivities::uploadingProfileImg($file);

    if (!$uploadedImage['success']) {
        $errorMessage = $uploadedImage['error'] ?? 'Image upload failed';
        throw CustomExceptions::globalException($errorMessage,$uploadedImage['status'] ?? 500);
    }

    return $uploadedImage;
}
private  function getDefaultProfileImg()
{
return[
        "success"=>true,
        "type"=>"doctor_default_img ",
        "data" => [
        "url" =>"https://res.cloudinary.com/dox9haqsi/image/upload/v1770923392/doctorUser_ubjpbl.png" ,
        "public_id"=>"doctorUser_ubjpbl",

         ]];

}
private function createDoctor(array $validated, array $license, array $uploadImage)
{
    $createdDoctor = DoctorQueries::create(
        $validated,
        $license['data'],
        $uploadImage['data']
    );

    if (!$createdDoctor['success']) {
        $errorMessage = $createdDoctor['error'] ?? 'Failed to create doctor';
        throw CustomExceptions::globalException($errorMessage,$createdDoctor['status'] ?? 500);
    }
    return $createdDoctor;
}



}
