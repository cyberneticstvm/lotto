<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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
        $token = '1a2b3c4d5e6f7g8h9i';
        $headers = collect($request->header())->transform(function ($item) {
            return $item[0];
        });
        if ($request->route()->getPrefix() === 'api') {
            if ($headers['authorization'] != $token) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid Authentication Token',
                ], 500);
            }
        }
        return $next($request);
    }
}
