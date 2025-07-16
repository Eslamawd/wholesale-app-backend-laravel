<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SealsMiddlware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
       public function handle(Request $request, Closure $next): Response
    {
    // Check if the user is authenticated and has the 'admin' role
    // If the user is authenticated and has the 'admin' role, allow the request to proceed
    // If the user is not authenticated or does not have the 'admin' role, return a 403 Forbidden response
           if (auth()->user() && auth()->user()->role === 'seals') {
        return $next($request);
    }

    return response()->json(['message' => 'Forbidden'], 403);
  
    }
}
