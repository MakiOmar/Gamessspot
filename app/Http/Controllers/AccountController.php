<?php

namespace App\Http\Controllers;

use App\Models\Account; // Assuming Account is the model
use Illuminate\Http\Request;

class AccountController extends Controller
{
    // Display the accounts table
    public function index()
    {
        // Assuming you want paginated accounts
        $accounts = Account::paginate(10); // Adjust pagination as needed

        // Return the view with the accounts data
        return view('manager.accounts', compact('accounts'));
    }
}
