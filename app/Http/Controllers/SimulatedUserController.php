<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SimulateUser;
use App\Http\Requests\SwitchUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class SimulatedUserController extends Controller
{
    public function __invoke(SwitchUserRequest $request): RedirectResponse
    {
        $userId = $request->integer('user_id');
        $request->session()->put(SimulateUser::SESSION_KEY, $userId);

        // Not back() to the previous page: the new identity may not be allowed to see it
        // (e.g. switching away from an admin while on the dashboard). Land on the page
        // each role actually starts at, same as HomeController does for a fresh visit.
        return User::findOrFail($userId)->isManager()
            ? to_route('dashboard')
            : to_route('contracts.index');
    }
}
