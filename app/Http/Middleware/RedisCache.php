<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RedisCache
{
    protected const CACHE_EXPIRY = 43200; // 6 hours

    public function handle(Request $request, Closure $next)
    {
        $key = $request->getRequestUri();
        $requestType = $request->segments()[1];
        $hashKey = "request:{$requestType}:{$key}";
        $cached = true;



        if (!app('redis')->exists($hashKey)) {

            $response = $next($request);
            $cached = false;

            if ($this->isError($response)) {
                return $response;
            }

            app('redis')->set(
                $hashKey,
                $response->original
            );
            app('redis')->expire($hashKey, self::CACHE_EXPIRY);
        }

        return response()->json(
            array_merge(
                [
                    'request_cached' => $cached,
                    'cache_expiration' => app('redis')->ttl($hashKey),
                ],
                json_decode(
                    app('redis')->get($hashKey),
                    true
                )
            )
        );
    }

    private function isError($response) {
        return isset($response->original['error']);
    }
}