<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class passwordAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $accessToken =$request->user()->currentAccessToken();
if(!$accessToken->can("resetPassword"))
    response()->json([
                "success"=>false,
                "error"=>"Unauthenticated to reset password",

            ],401);
        return $next($request);
    }
}