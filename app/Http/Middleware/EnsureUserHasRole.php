<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Traits\ApiResponse;

class EnsureUserHasRole
{
    use ApiResponse;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $role
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $role = null)
    {
        // Check if the user is authenticated
        if (!Auth::check()) {
            // Check if the request is JSON or wants JSON response
            if ($request->isJson() || $request->wantsJson() || $request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => 'You must be logged in or if you are a guest you must be registered to access this page'], 401);
            } else {
                return redirect()->route('signin')->with('message', toast('You must be logged in or if you are a guest you must be registered to access this page', 'error'));
            }
        }

        if ($role && Auth::user()->role !== $role) {
            abort(403, 'You do not have permission to access this page or this resource.');
        }

        return $next($request);
    }
}
