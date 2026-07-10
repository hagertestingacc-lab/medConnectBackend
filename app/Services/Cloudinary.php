<?php
namespace App\Services;

use App\Exceptions\DoctorExceptions;
use Cloudinary\Api\Exception\ApiError;
use Cloudinary\Api\Upload\UploadApi;
use Illuminate\Support\Facades\Log;

class Cloudinary
{

public  function upload (string $file,array $options)
{
    try {
    $uploadAPi = new UploadApi();
  $imageResult = $uploadAPi->upload($file,   $options);
return $this->successUploadingResponse($imageResult);
    }
    catch(ApiError $e)
    {

        Log::error("ApiError creation failed".$e->getMessage());
/*  */            return $this->faildUploadingResponse($e);
    }
    catch(\Exception $e){


         Log::error("image creation failed".$e->getMessage());

                   return $this->faildUploadingResponse($e);
    }


}
public function destroy($publicId)
{
    try {
        $uploadApi = new UploadApi();
        $uploadApi->destroy($publicId);
    } catch (ApiError $e) {
        Log::error('ApiError destroy failed: ' . $e->getMessage());
        return $this->faildUploadingResponse($e);
    } catch (\Exception $e) {
        Log::error('Image destroy failed: ' . $e->getMessage());
        return $this->faildUploadingResponse($e);
    }
}


private function successUploadingResponse($imageResult)
{
 return   [
    "success" => true,
     "type"=>"cloudinary",
    "data" => [
        "url" => $imageResult['secure_url'] ?? $imageResult['url'],
        "public_id" => $imageResult['public_id'],


    ]
];
}
private function faildUploadingResponse($e)
{
 return   [
                "success" => false,
                "error" => 'Failed to upload image',
                 "type"=>"doctor_own_img ",
                 "data"=>[],
                "message" => $e->getMessage(), // for logging
                "status" => 500
            ];
}

}