<?php

namespace App\Policies;

use App\Models\AllUserPart\AllUser;
use App\Models\customRequestsPart\customRequest;
use App\Models\customRequestsPart\OfferRequest;
use App\Models\DoctorPart\Doctor;
use App\Models\ProductPart\Product;
use App\Models\User;

class AuthPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }



    public function productDoctor(AllUser $user, Product $product)
    {
        return   $user->doctor->id ==   $product->doctor_id;;
    }
    public function productSupplier(AllUser $user, Product $product)
    {
        return   $user->supplier->id ==   $product->supplier_id;;
    }
    public function offerSupplier(AllUser $user, OfferRequest $offerRequest)
    {
        return   $user->supplier->id ==  $offerRequest->supplier_id;
    }
    public function requestDoctor(AllUser $user,customRequest $customRequest)
    {
        echo "ddd";
        echo $customRequest->doctor_id;
        echo $user->doctor->id;
        return   $user->doctor->id ==  $customRequest->doctor_id;
    }
    public function offerResponceDoctor(AllUser $user,OfferRequest $offerRequest)
    {
        return   $user->doctor->id ==  $offerRequest->customRequest->doctor_id;
    }
}
