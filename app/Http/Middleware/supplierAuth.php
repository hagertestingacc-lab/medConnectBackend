<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class supplierAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
                  if(!$request->user()->tokenCan("supplier"))
    return  response()->json([
                "success"=>false,
                "error"=>"This is action only for suppliers",

            ],401);
        return $next($request);
    }
}
