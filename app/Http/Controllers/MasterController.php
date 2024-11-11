<?php

namespace App\Http\Controllers;

use App\Models\Master;
use Illuminate\Http\Request;

class MasterController extends Controller
{
    /**
     * Display a listing of the records.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $masters = Master::all();
        return view('masters.index', compact('masters'));
    }

    /**
     * Show the form for creating a new record.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('masters.create');
    }

    /**
     * Store a newly created record in the database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'mail' => 'required|email|unique:masters,mail',
            'password' => 'required|string|min:6',
            'region' => 'required|string',
            'value' => 'nullable|numeric',
            'rate' => 'nullable|numeric',
        ]);

        $master = new Master([
            'mail' => $request->get('mail'),
            'password' => bcrypt($request->get('password')),
            'region' => $request->get('region'),
            'value' => $request->get('value'),
            'rate' => $request->get('rate'),
        ]);
        $master->save();

        return redirect()->route('masters.index')->with('success', 'Record created successfully.');
    }

    /**
     * Show the form for editing a specific record.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $master = Master::findOrFail($id);
        return view('masters.edit', compact('master'));
    }

    /**
     * Update the specified record in the database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'mail' => 'required|email|unique:masters,mail,' . $id,
            'password' => 'nullable|string|min:6',
            'region' => 'required|string',
            'value' => 'nullable|numeric',
            'rate' => 'nullable|numeric',
        ]);

        $master = Master::findOrFail($id);
        $master->mail = $request->get('mail');
        if ($request->filled('password')) {
            $master->password = bcrypt($request->get('password'));
        }
        $master->region = $request->get('region');
        $master->value = $request->get('value');
        $master->rate = $request->get('rate');
        $master->save();

        return redirect()->route('masters.index')->with('success', 'Record updated successfully.');
    }

    /**
     * Remove the specified record from the database.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $master = Master::findOrFail($id);
        $master->delete();

        return redirect()->route('masters.index')->with('success', 'Record deleted successfully.');
    }
}
