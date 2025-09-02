<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class DepartmentsController extends Controller
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
        $role = Role::all(); // Fetch all positions
        return view('master.departments.create', compact('role')); // return the form view with the positions
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

         // Validation
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        // Create a new user
        $department = new Department();
        $department->parent_id = $request->parent_id;
        $department->name = $request->name;
        $department->save();
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
