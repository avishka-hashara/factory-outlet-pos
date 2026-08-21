<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * Usage on routes: ->middleware('permission:pos')
     */
    public function handle(Request $request, Closure $next, string $page): Response
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->canAccess($page)) {
            // If it's an AJAX/JSON request return 403 JSON
            if ($request->expectsJson()) {
                return response()->json(['error' => 'You do not have permission to access this resource.'], 403);
            }

            return response()->view('errors.403', [
                'message' => 'You do not have permission to access this page.',
            ], 403);
        }

        return $next($request);
    }
}
