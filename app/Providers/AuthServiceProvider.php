<?php

namespace App\Providers;

use App\Models\ProductPart\Product;
use App\Models\customRequestsPart\customRequest;
use App\Models\customRequestsPart\OfferRequest;
use App\Policies\AuthPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{


protected $policies=[
    Product::class => AuthPolicy::class,
    customRequest::class => AuthPolicy::class,
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