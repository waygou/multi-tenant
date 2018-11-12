<?php

namespace Waygou\MultiTenant\Middleware;

use Closure;
use Waygou\MultiTenant\Exceptions\DifferentIpException;

class SameIp
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $ip)
    {
        if ($request->ip() != $ip) {
            throw new DifferentIpException("The request ip address is invalid.");
        }

        return $next($request);
    }
}
