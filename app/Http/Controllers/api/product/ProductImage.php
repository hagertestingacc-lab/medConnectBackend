<?php

namespace App\Http\Controllers\api\product;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductPart\ProductImage as Image;

class ProductImage extends Controller
{
    //by supplier
    public function delete($id)
    {

    try{

   $image= Image::where("id",$id)->delete() ;
if(!$image)
     return response()->json([
        "success"=>false,
        "message"=>"Image Not found",
    ],404);
    
     return response()->json([
        "success"=>true,
        "message"=>"Image deleted successfully",
    ],200);
    
    
    }catch(\Exception $e)
    {
         return response()->json([
        "success"=>false,
        "message"=>"An issue occured".$e->getMessage(),
    ],500);
    }
        
    }
}