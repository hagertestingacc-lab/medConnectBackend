<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class adminSupplierAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
                try {

                if(!$request->user()->currentAccessToken())
                     return  response()->json([
                "success"=>false,
                "error"=>"Token not found",

            ],404);

          if(!($request->user()->tokenCan("admin") || $request->user()->tokenCan("supplier")))
    return  response()->json([
                "success"=>false,
                "error"=>"This is action need higher premission",

            ],401);
                }
                catch(\Exception $e)
                 {
                       return  response()->json([
                "success"=>false,
                "error"=>"An issue occured".$e->getMessage(),

            ],500);
                 }

        return $next($request);
    }
}
