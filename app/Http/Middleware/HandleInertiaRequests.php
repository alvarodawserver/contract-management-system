<?php

namespace App\Http\Middleware;

use App\Enums\RoleSlug;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'simulation' => fn (): array => [
                'current' => $request->user()?->id,
                'users' => $this->simulatedUsers(),
            ],
        ];
    }

    /**
     * The users the demo selector can switch to: admin first, then heads, then employees.
     *
     * @return array<int, array{id: int, name: string, role: string|null, role_label: string|null, department: string|null}>
     */
    private function simulatedUsers(): array
    {
        return User::query()
            ->with(['role', 'employee.department'])
            ->get()
            ->sortBy(fn (User $user): string => $this->rolePriority($user).$user->name)
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role?->slug->value,
                'role_label' => $user->role?->name,
                'department' => $user->employee?->department?->name,
            ])
            ->values()
            ->all();
    }

    private function rolePriority(User $user): int
    {
        return match ($user->role?->slug) {
            RoleSlug::Admin => 0,
            RoleSlug::DepartmentHead => 1,
            RoleSlug::DelegatedEmployee => 2,
            default => 3,
        };
    }
}
