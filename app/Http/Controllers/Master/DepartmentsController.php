<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Position;
use App\Services\MasterManagementService;
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
        $departments = MasterManagementService::getDepartmentListOptions()->get();
        return view('master.departments.create', compact('departments')); // return the form view with the positions
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

        // dd($request->all());

        // Create a new user
        $department = new Department();
        $department->parent_id = $request->parent_id;
        $department->name = $request->name;
        $department->code = $request->code;
        $department->approval_by = $request->approval_by;
        $department->limit_submission = $request->limit_submission;
        $department->save();

        MasterManagementService::storeLogActivity('create-departement',$department->id,$department->name);


        return redirect()->route('departments.index')->with('success', 'User created successfully');

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
         $department = Department::with(['parent'])->findOrFail($id);

         return view('master.departments.show', compact('department'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $department = Department::find($id);
        $departments = MasterManagementService::getDepartmentListOptions()->get();
        return view('master.departments.edit', compact('departments','department')); // return the form view with the positions
    }

    /**
     * Update the specified resource in storage.
     */
public function update(Request $request, string $id)
{
    // Validasi input
    $request->validate([
        'name' => 'required|string|max:255',
        'code' => 'required|string|max:255',
        'parent_id' => 'nullable|exists:departments,id',
        'limit_submission' => 'required|integer',
        'approval_by' => 'required|in:self,parent,central',
    ]);

    // Update data departemen
    $department = Department::findOrFail($id);
    $department->name = $request->name;
    $department->code = $request->code;
    $department->parent_id = $request->parent_id;
    $department->limit_submission = $request->limit_submission;
    $department->approval_by = $request->approval_by;
    $department->save();

        MasterManagementService::storeLogActivity('update-departement',$department->id,$department->name);


    return redirect()->route('departments.index')->with('success', 'Departemen berhasil diupdate.');
}

public function destroy(string $id)
{
    $department = Department::findOrFail($id);
    $department->delete();

        MasterManagementService::storeLogActivity('delete-departement',$department->id,$department->name);

    return redirect()->route('departments.index')->with('success', 'Departemen berhasil dihapus.');
}
}
