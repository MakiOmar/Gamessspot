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
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        // Validate the form data
        $request->validate(
            array(
                'phone'    => 'required|string',
                'password' => 'required|string',
            )
        );

        // Attempt to log the manager in using phone number and password
        if (
            Auth::guard('admin')->attempt(
                array(
                'phone'    => $request->phone,
                'password' => $request->password,
                ),
                $request->remember
            )
        ) {
            // Check if the authenticated user has 'admin', 'sales', or 'accountant' role
            if (
                Auth::guard('admin')->user()->roles->contains(
                    function ($role) {
                        return in_array($role->name, array( 'admin', 'sales', 'accountant', 'account manager' ));
                    }
                )
            ) {
                // If the user has one of the allowed roles, redirect to the dashboard
                return redirect()->intended(route('manager.dashboard'));
            }
            // If the user doesn't have one of the specified roles, log them out and show an error
            Auth::guard('admin')->logout();
            return redirect()->route('login')->withErrors(array( 'You do not have the required role to access this area.' ));
        }

        // If unsuccessful, redirect back with an error
        return redirect()->back()->withInput($request->only('phone', 'remember'))->withErrors(
            array(
                'phone' => 'These credentials do not match our records.',
            )
        );
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
