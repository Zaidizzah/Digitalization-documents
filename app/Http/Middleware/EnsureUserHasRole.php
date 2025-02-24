<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
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
        if (!Auth::check()) {
            return redirect()->route('signin')->with('message', toast('You must be logged in or if you are a guest you must be registered to access this page', 'error'));
        }

        if ($role && Auth::user()->role !== $role) {
            abort(403, 'You do not have access to this page.');
        }

        return $next($request);
    }
}
