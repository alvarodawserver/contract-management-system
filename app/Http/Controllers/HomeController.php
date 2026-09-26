<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Send the user straight to the view that matches their role: a department head or
     * admin manages from the dashboard, a delegated employee starts at their own contracts.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        return $request->user()?->isManager()
            ? to_route('dashboard')
            : to_route('contracts.index');
    }
}
