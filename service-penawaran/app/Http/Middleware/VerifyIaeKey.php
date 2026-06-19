<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyIaeKey
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle($request, \Closure $next)
{
    if ($request->header('X-IAE-KEY') !== '102022400212') {
        return response()->json(['status' => 'error', 'message' => 'Unauthorized: Invalid X-IAE-KEY', 'errors' => null], 401);
    }
    return $next($request);
}
}
