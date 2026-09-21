<?php

namespace App\Http\Middleware;

use App\Enums\RoleSlug;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets the demo run without a login: acts as the user picked in the selector, or as the admin.
 * A really authenticated user is never replaced.
 */
class SimulateUser
{
    public const SESSION_KEY = 'simulated_user_id';

    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            $user = $this->simulatedUser($request);

            if ($user !== null) {
                Auth::setUser($user);
            }
        }

        return $next($request);
    }

    private function simulatedUser(Request $request): ?User
    {
        return User::query()->with(['role', 'employee'])->whereKey($request->session()->get(self::SESSION_KEY))->first()
            ?? User::query()->with(['role', 'employee'])
                ->whereRelation('role', 'slug', RoleSlug::Admin->value)
                ->first();
    }
}
