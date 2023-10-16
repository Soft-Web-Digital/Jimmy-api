<?php

namespace App\Http\Middleware;

use App\Exceptions\InsecurePasswordException;
use Closure;
use Illuminate\Http\Request;

class RequireSecurePassword
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (isset($request->user()->password_unprotected) && $request->user()->password_unprotected) {
            throw new InsecurePasswordException();
        }

        return $next($request);
    }
}
