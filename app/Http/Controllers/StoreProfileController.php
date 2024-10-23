<?php

namespace App\Http\Controllers;

use App\Models\StoresProfile;
use Illuminate\Http\Request;

class StoreProfileController extends Controller
{
    public function index()
    {
        $storeProfiles = StoresProfile::withCount('orders')->paginate(10);
        return view('manager.stores', compact('storeProfiles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);
        $validated['phone_number'] = $validated['phone'];
        unset($validated['phone']);
        StoresProfile::create($validated);

        return response()->json(['message' => 'Store Profile created successfully']);
    }

    public function edit($id)
    {
        $storeProfile = StoresProfile::findOrFail($id);
        return response()->json($storeProfile);
    }

    public function update(Request $request, $id)
    {
        $storeProfile = StoresProfile::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);
        $validated['phone_number'] = $validated['phone'];
        unset($validated['phone']);
        $storeProfile->update($validated);

        return response()->json(['message' => 'Store Profile updated successfully']);
    }

    public function search(Request $request)
    {
        $query = $request->input('search');

        $storeProfiles = StoresProfile::where('name', 'like', "%{$query}%")
            ->orWhere('phone_number', 'like', "%{$query}%")
            ->get();

        return view('manager.partials.store_profile_rows', compact('storeProfiles'))->render();
    }
}
