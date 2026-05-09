<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     * Usage in route: ->middleware('role:admin')
     *                 ->middleware('role:admin,manager')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! in_array($request->user()->role, $roles)) {
            return response()->json([
                'message' => 'Forbidden. Insufficient permissions.',
                'error'   => 'FORBIDDEN',
            ], 403);
        }

        return $next($request);
    }
}