<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SimulateUser;
use App\Http\Requests\SwitchUserRequest;
use Illuminate\Http\RedirectResponse;

class SimulatedUserController extends Controller
{
    public function __invoke(SwitchUserRequest $request): RedirectResponse
    {
        $request->session()->put(SimulateUser::SESSION_KEY, $request->integer('user_id'));

        return back();
    }
}
