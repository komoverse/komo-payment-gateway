<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\APIKeyModel;

class APIKeyAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        header('Content-Type: application/json; charset=UTF-8');
        header('Access-Control-Allow-Origin: *');
        if ($request->header('X-Api-Key')) {
            if (APIKeyModel::findAPIKey($request->header('X-Api-Key'))) {
                $response = $next($request);
                $response->header('Content-Type','application/json; charset=UTF-8');
                $response->header('Access-Control-Allow-Origin','*');
                return $response;
            } else {
                return response()->json([
                    "apiAccessDenied" => "Your application is not authorized to perform this request. If you wish to use the KOMO API in your application, contact the Komoverse team to reach out for an agreement."
                ], 403);
            }
        } else {
            return response()->json([
                "apiKeyRequired" => "An API key is required to perform this request. If you wish to use the KOMO API in your application, contact the Komoverse team to reach out for an agreement."
            ], 403);
        }
    }
}
