<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

class ValidateRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = Config::get('myconfig.authkey');
        $headers = collect($request->header())->transform(function ($item) {
            return $item[0];
        });
        if ($request->routeIs('api.*')) {
            if ($headers['authorization'] == $token) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid Authentication Token',
                ], 500);
            }
        }
        return $next($request);
    }
}
