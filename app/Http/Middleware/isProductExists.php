<?php

namespace App\Http\Middleware;

use App\Models\ProductPart\Product;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class isProductExists
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request ,Closure $next,$id): Response
    {
    $product=Product::find($id);
    
if(!$product)
      return response()->json([
        "success"=>false,
        "message"=>"Product not found",
    ],404);
    $request->attributes->set("product", $product);
        return $next($request);
    }
}