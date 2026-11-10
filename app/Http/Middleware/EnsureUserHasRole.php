<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if (($user->hasSuperAdminMarker() || $user->role === UserRole::AdministratorUtama) && ! $user->isSuperAdmin()) {
            abort(403);
        }

        if ($user->isSuperAdmin() && ! in_array(UserRole::AdministratorUtama->value, $roles, true)) {
            abort(403);
        }

        $userRole = $user->role;

        if ($userRole instanceof UserRole) {
            if (in_array($userRole->value, $roles, true)) {
                return $next($request);
            }
        } elseif (is_string($userRole)) {
            if (in_array($userRole, $roles, true)) {
                return $next($request);
            }
        }

        abort(403);
    }
}
