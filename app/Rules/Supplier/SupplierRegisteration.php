<?php
namespace App\Rules\Supplier;

use App\Exceptions\CustomExceptions;
use App\Http\Requests\supplierDataRequest;
use App\Models\AllUserPart\AllUser;
use App\Models\SupplierPart\Supplier;
use App\Services\Cloudinary;
use App\Services\Supplier\SupplierActivities ;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class SupplierRegisteration
{


public static function register(supplierDataRequest $request, array $validated)
{
    $upload_imgs=[];
try
{

$upload_imgs = self::uploadSupplierImages($request);


 $reuslt = DB::transaction(function () use ($validated ,$upload_imgs ){
 $user = self::createUser($validated);
self::createSupplier($validated,$upload_imgs, $user["id"] );


 return [
    'success' => true,
'message' => 'Welcome! Your supplier application is in review. Check your email soon for an update on your registration status.',
"status"=>201
];
});

return  $reuslt;
 }catch(\Exception $e) {
    if(\count($upload_imgs))
   self::deleteSupplierImages($upload_imgs);

        Log::error('Supplier creation failed: ' . $e->getMessage());
/*         echo 'Supplier creation failed: ' . $e->getMessage() . $e->getCode();
 */        return [
            'success' => false,
            'error' => 'Supplier signup failed. Please try again.'.$e->getMessage(),
         'status' =>$e->getCode()==422?422: 500

                      ];
}

}

public static function uploadSupplierImages($request)
{
    $imageNames=["company_image_url","tax_card_image","certificate_image"];

    foreach($imageNames as $imageName)
        {
            if(!$request->hasFile($imageName))
            CustomExceptions::globalException("Upload all needed images please $imageName",422);

            if(!$request->file($imageName)->isValid())
            CustomExceptions::globalException("Failed to upload: invalid image $imageName",422);


        }

    $uploaded_company_image = SupplierActivities::uploadImage($request->file("company_image_url"));


    if(!$uploaded_company_image["success"])
         CustomExceptions::globalException("Failed to upload: invalid  company image",422);

        $uploaded_tax_card = SupplierActivities::uploadImage($request->file("tax_card_image"));

        if(!$uploaded_tax_card["success"] )
            {
/*                                 echo "unsuccess taxCard";
 */
             SupplierActivities::deleteImage($uploaded_company_image["data"]["public_id"]);
              CustomExceptions::globalException("Failed to upload: invalid  tax card",422);
            }


        $upload_certificate_image = SupplierActivities::uploadImage($request->file("certificate_image"));

                if(!$upload_certificate_image["success"] )
               {
/*                 echo "unsuccess upload_certificate_image";
 */                SupplierActivities::deleteImage($uploaded_company_image["data"]["public_id"]);
                SupplierActivities::deleteImage($uploaded_tax_card["data"]["public_id"]);
                CustomExceptions::globalException("Failed to upload: invalid certificate image",422);


            }

/*             var_dump($uploaded_company_image['data'],"upload");
 */
return [
"cloudinary_company_image"=>$uploaded_company_image['data'],
"cloudinary_tax_card"=>$uploaded_tax_card['data'],
"cloudinary_certificate"=>$upload_certificate_image['data'],
];
}


private static function deleteSupplierImages($upload_imgs)
{

/* var_dump($upload_imgs,"delete all imgs");
 */SupplierActivities::deleteImage($upload_imgs["cloudinary_company_image"]["public_id"]);
 SupplierActivities::deleteImage($upload_imgs["cloudinary_tax_card"]["public_id"]);   SupplierActivities::deleteImage($upload_imgs["cloudinary_certificate"]["public_id"]);

}

private static function createUser($validated)
{
  $user =    AllUser::create([
        "fullname"=>$validated["full_name"],
        "email"=>$validated["email"],
        "address"=>$validated["address"],
        "password"=>Hash::make($validated["password"] ),
        "role"=>"supplier",


      ]);
      return $user;
}
private static function createSupplier($validated,$upload_imgs,$user_table_id)
{

/* var_dump($upload_imgs);
echo $upload_imgs["cloudinary_company_image"]["public_id"];
echo $upload_imgs["cloudinary_tax_card"]["public_id"];
echo $upload_imgs["cloudinary_certificate"]["public_id"];
 */Supplier::create([
    "user_table_id"=>$user_table_id,
    "national_id" => $validated["national_id"],
    "phone" => $validated["phone"],
    "company_name" => $validated["company_name"],
    "governorate" => $validated["governorate"],
    "certificate_name" => $validated["certificate_name"],
    "company_image_url" => $upload_imgs["cloudinary_company_image"]["url"],
    "certificate_image" => $upload_imgs["cloudinary_certificate"]["url"],
    "tax_card_image" => $upload_imgs["cloudinary_tax_card"]["url"], // fixed: was using certificate
    "cloudinary_company_image_id" => $upload_imgs["cloudinary_company_image"]["public_id"],
    "cloudinary_tax_card_id" => $upload_imgs["cloudinary_tax_card"]["public_id"],
    "cloudinary_certificate_id" => $upload_imgs["cloudinary_certificate"]["public_id"],
]);
}




}
