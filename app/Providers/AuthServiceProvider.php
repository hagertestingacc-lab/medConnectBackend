<?php

namespace App\Providers;

use App\Models\ProductPart\Product;
use App\Models\CustomRequestsPart\CustomRequest;
use App\Models\CustomRequestsPart\OfferRequest;
use App\Policies\AuthPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{


protected $policies=[
    Product::class => AuthPolicy::class,
    CustomRequest::class => AuthPolicy::class,
    OfferRequest::class => AuthPolicy::class,
];
    

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
         $this->registerPolicies(); 
    }
}
