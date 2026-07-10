<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class doctorAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
          if(!$request->user()->tokenCan("doctor"))
      return response()->json([
                "success"=>false,
                "message"=>"This is action only for doctors",

            ],status:401 );
        return $next($request);
    }
}