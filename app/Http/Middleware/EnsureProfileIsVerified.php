<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->profile) {
            return response()->json(['message' => 'Unauthenticated or profile not found.'], 401);
        }

        if (! $user->profile->is_verified) {
            return response()->json([
                'message' => 'This action requires a verified profile.',
            ], 403);
        }

        // Check if verification has expired
        if ($user->profile->verified_expires_at && $user->profile->verified_expires_at->isPast()) {
            return response()->json([
                'message' => 'Your profile verification has expired.',
            ], 403);
        }

        return $next($request);
    }
}
