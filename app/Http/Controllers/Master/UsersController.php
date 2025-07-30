<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class UsersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::all(); // Fetch all users
        return view('master.users.index', compact('users')); // return a view to display the users
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $positions = Position::all(); // Fetch all positions
        $department = Department::all(); // Fetch all positions
        return view('master.users.create', compact('positions','department')); // return the form view with the positions
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validation
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'position_id' => 'required|exists:positions,id',
            'department_id' => 'nullable|exists:departments,id',
            'role' => 'required|string'
        ]);

        // Create a new user
        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->position_id = $request->position_id;
        $user->department_id = $request->department_id;
        $user->save();


        return redirect()->route('master.users.index')->with('success', 'User created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::findOrFail($id); // Fetch user by ID
        return view('master.users.show', compact('user')); // Return view to display user details
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $user = User::findOrFail($id); // Fetch user by ID
        $positions = Position::all(); // Fetch all positions
        $department = Department::all(); // Fetch all positions
        return view('master.users.edit', compact('user', 'positions','department')); // Return form view with user and positions
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Validation
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8|confirmed',
            'position_id' => 'required|exists:positions,id',
            'department_id' => 'nullable|exists:departments,id',
            'role' => 'required|string'
        ]);

        $user = User::findOrFail($id); // Fetch user by ID

        // Update user details
        $user->name = $request->name;
        $user->email = $request->email;
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        $user->position_id = $request->position_id;
        $user->department_id = $request->department_id;
        $user->save();

        // Update the user's role
        $role = Role::findByName($request->role);
        $user->syncRoles([$role]);

        return redirect()->route('master.users.index')->with('success', 'User updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id); // Fetch user by ID
        $user->delete(); // Delete user

        return redirect()->route('master.users.index')->with('success', 'User deleted successfully');
    }
}
