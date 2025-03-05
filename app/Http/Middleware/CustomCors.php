<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CustomCors
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowedOrigin = 'https://staging-app.digitalschool.tech';
        $incomingOrigin = $request->headers->get('Origin');

        Log::info('CORS Check', [
            'incoming_origin' => $incomingOrigin,
            'allowed_origin' => $allowedOrigin,
            'headers' => $request->headers->all()
        ]);

        if ($incomingOrigin !== $allowedOrigin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return $next($request)
            ->header('Access-Control-Allow-Origin', $allowedOrigin)
            ->header('Access-Control-Allow-Methods', 'POST')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }
} 