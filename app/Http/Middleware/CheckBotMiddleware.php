<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBotMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Check User Agent
        $userAgent = $request->header('User-Agent');
        if (empty($userAgent) || $this->isBotUserAgent($userAgent)) {
            return response()->json(['message' => 'Access denied.'], 403);
        }

        // 2. Check for Honeypot field (if present in request)
        if ($request->has('website') && !empty($request->input('website'))) {
            return response()->json(['message' => 'Access denied.'], 403);
        }

        return $next($request);
    }

    private function isBotUserAgent(string $userAgent): bool
    {
        $bots = [
            'bot', 'crawl', 'slurp', 'spider', 'curl', 'wget', 'python', 'java', 'headless'
        ];

        foreach ($bots as $bot) {
            if (stripos($userAgent, $bot) !== false) {
                return true;
            }
        }

        return false;
    }
}
