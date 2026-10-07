<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    /** `admin` lets any staff in; `admin:orders` etc. also needs that area (see User::ROLES). */
    public function handle(Request $request, Closure $next, ?string $area = null): Response
    {
        $user = $request->user();
        abort_unless($user?->isStaff() && ($area === null || $user->hasAccess($area)), 403);

        return $next($request);
    }
}
