<?php

namespace App\Http\Middleware;

use App\Exceptions\NotAllowedException;
use App\Exceptions\TwoFaRequiredException;
use Closure;
use Illuminate\Http\Request;

class AuthenticateTwoFa
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  int|bool  $complete
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $complete = 1)
    {
        if ((bool) $complete) {
            if (!$request->user()->tokenCan('*')) {
                throw new TwoFaRequiredException();
            }
        } else {
            if ($request->user()->tokenCan('*')) {
                throw new NotAllowedException('You have completed your authentication already');
            }
        }

        return $next($request);
    }
}
