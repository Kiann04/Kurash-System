<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $provided = $request->header('X-API-KEY');

        if (!$provided) {
            return response()->json([
                'success' => false,
                'error' => 'missing_api_key',
                'message' => 'X-API-KEY header is required',
                'details' => null,
            ], 401);
        }

        $keys = env('KURASH_SCOREBOARD_API_KEYS');
        $single = env('KURASH_SCOREBOARD_API_KEY', 'kurash-scoreboard');
        $allow = collect(explode(',', (string) $keys))
            ->map(fn ($k) => trim($k))
            ->filter()
            ->push($single)
            ->unique()
            ->all();

        if (!in_array($provided, $allow, true)) {
            return response()->json([
                'success' => false,
                'error' => 'forbidden_api_key',
                'message' => 'API key is not allowed',
                'details' => null,
            ], 403);
        }

        return $next($request);
    }
}

