<?php

namespace App\Http\Middleware;

use App\Exceptions\BlockedAccountException;
use Closure;
use Illuminate\Http\Request;

class EnsureAccountIsUnblocked
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
        if ($request->user()->blocked_at) {
            throw new BlockedAccountException();
        }

        return $next($request);
    }
}
