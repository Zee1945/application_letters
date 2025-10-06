<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Position;
use App\Services\MasterManagementService;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class PositionsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = MasterManagementService::getRoleListOptions()->get();
        return view('master.positions.create', compact('roles')); // return the form view with the positions
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

         // Validation
        $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|string'
        ]);

        // Create a new user
        $position = new Position();
        $position->name = $request->name;
        $position->save();
               // Assign the role
      
        $position->assignRole($request->role);
         return redirect()->route('positions.index')->with('success', 'Jabatan berhasil diupdate.');


    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $position = Position::find($id);
        return view('master.positions.show', compact('position'));
        
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $position = Position::findOrFail($id);
        $roles = MasterManagementService::getRoleListOptions()->get();
        return view('master.positions.edit', compact('position', 'roles'));

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|string'
        ]);

        $position = Position::findOrFail($id);
        $position->name = $request->name;
        $position->save();

        // Sync role
        $position->syncRoles([$request->role]);

        return redirect()->route('positions.index')->with('success', 'Jabatan berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        $position = Position::findOrFail($id);
        $position->delete();

        return redirect()->route('positions.index')->with('success', 'Jabatan berhasil dihapus.');

    }
}
