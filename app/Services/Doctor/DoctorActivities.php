<?php

namespace App\Services\Doctor;

use App\Services\Cloudinary;

class DoctorActivities
{
    public const DEFAULT_PROFILE_IMAGE_PUBLIC_ID = 'doctorUser_ubjpbl';
    public const DEFAULT_PROFILE_IMAGE_URL = 'https://res.cloudinary.com/dox9haqsi/image/upload/v1770923392/doctorUser_ubjpbl.png';

    public static function uploadingProfileImg($profile_image_url, ?string $publicId = null)
    {
        $Cloudinary = new Cloudinary();
        $uplodImage = $Cloudinary->upload($profile_image_url->getRealPath(), [
            'public_id' => $publicId ?? 'profile_image_url' . uniqid(true),
            'use_filename' => true,
            'overwrite' => true
        ]);

        return $uplodImage;
    }

    public static function deleteProfileImg($publicId)
    {
        if ($publicId === self::DEFAULT_PROFILE_IMAGE_PUBLIC_ID) {
            return;
        }

        $Cloudinary = new Cloudinary();
        $uplodImage = $Cloudinary->destroy($publicId);

        return $uplodImage;
    }
}
