<?php
nameSpace App\Services\Supplier;
use App\Services\Cloudinary;

class SupplierActivities
{

public static function uploadImage($image_url)
{

       $Cloudinary=new Cloudinary();
        $uplodImage=$Cloudinary->upload($image_url->getRealPath() , [
        'public_id' => 'supplier'.uniqid(true),
        'use_filename' => true,
        'overwrite' => true]);

        return $uplodImage;

        }


        public static function  deleteImage($publicId)
{

       $Cloudinary=new Cloudinary();
        $uplodImage=$Cloudinary->destroy($publicId);

        return $uplodImage;
        }



}