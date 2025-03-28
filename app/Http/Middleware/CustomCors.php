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
        // $stagingIp = '138.201.173.118';
        $stagingIp = '46.99.33.53';

        Log::info('CORS Check', [
            'incoming_origin' => $incomingOrigin,
            'allowed_origin' => $allowedOrigin,
            'client_ip' => $request->ip(),
            'headers' => $request->headers->all()
        ]);

        // Allow staging IP to bypass CORS
        if ($request->ip() === $stagingIp) {
            return $next($request);
        }

        // Regular CORS check for other requests
        if ($incomingOrigin !== $allowedOrigin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return $next($request)
            ->header('Access-Control-Allow-Origin', $allowedOrigin)
            ->header('Access-Control-Allow-Methods', 'POST')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }
} 