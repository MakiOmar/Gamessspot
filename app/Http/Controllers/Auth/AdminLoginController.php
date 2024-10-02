<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminLoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest:admin')->except('logout');
    }

    /**
     * Show the manager (admin) login form.
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        return view('auth.manager-login'); // Or you could rename the view to manager-login
    }

    /**
     * Handle the admin login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        // Validate the form data
        $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        // Attempt to log the manager in using phone number and password
        if (Auth::guard('admin')->attempt(['phone' => $request->phone, 'password' => $request->password, 'role' => 0], $request->remember)) {
            // If successful, redirect to the intended manager area
            return redirect()->intended(route('manager.dashboard'));
        }

        // If unsuccessful, redirect back with an error
        return redirect()->back()->withInput($request->only('phone', 'remember'))->withErrors([
            'phone' => 'These credentials do not match our records.',
        ]);
    }

    /**
     * Log the admin out.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout()
    {
        Auth::guard('admin')->logout();
        return redirect('/manager/login');
    }
}
