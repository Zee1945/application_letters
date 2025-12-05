<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use App\Models\Position;
use App\Services\AuthService;
use App\Services\MasterManagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class UsersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::all(); // Fetch all users
        return view('users.index', compact('users')); // return a view to display the users
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        
        $positions = Position::restricted()->get(); // Fetch all positions
        $department = MasterManagementService::getDepartmentListOptions()->get();
        return view('users.create', compact('positions','department')); // return the form view with the positions
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
     
        // Validation
        $request->validate([
            'name' => 'required|string|max:255',
            // 'name_without_degree' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'position_id' => 'required|exists:positions,id',
            'department_id' => 'nullable|exists:departments,id',
        ]); 

        // Create a new user
        $user = new User();
        $user->name = $request->name;
        $user->name_without_degree = $request->name_without_degree;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->position_id = $request->position_id;
        $user->department_id = $request->department_id;
        $user->save();
        MasterManagementService::storeLogActivity('create-user',$user->id,$user->name);
        

        return redirect()->route('users.index')->with('success', 'User created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // $user = User::findOrFail($id); // Fetch user by ID
        $user =User::where('id',$id)->restricted()->first();
        if (!$user) {
            abort(404);
        }
        return view('users.show', compact('user')); // Return view to display user details
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $user =User::where('id',$id)->restricted()->first();
        if (!$user) {
            abort(404);
        }
        $positions = Position::restricted()->get(); // Gunakan scope restricted
        $department = MasterManagementService::getDepartmentListOptions()->get();
        return view('users.edit', compact('user', 'positions','department')); // Return form view with user and positions
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
        // Validation
        $request->validate([
            'name' => 'required|string|max:255',
            // 'name_without_degree' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8|confirmed',
            'position_id' => 'required|exists:positions,id',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $user = User::findOrFail($id); // Fetch user by ID

        // Update user details
        $user->name = $request->name;
        $user->name_without_degree = $request->name_without_degree;
        $user->email = $request->email;
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        $user->position_id = $request->position_id;
        $user->department_id = $request->department_id;
        $user->save();

    //    if (!$user) {
    //         return redirect()->back()->withInput()->with('error', 'Gagal update user: ' . $e->getMessage());
    //    }
        MasterManagementService::storeLogActivity('update-user',$user->id,$user->name);
        return redirect()->route('users.index')->with('success', 'User updated successfully');
    } catch (\Exception $e) {
        return redirect()->back()->withInput()->with('error', 'Gagal update user: ' . $e->getMessage());
    }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user =User::where('id',$id)->restricted()->first();
        $current_user = AuthService::currentAccess();
        // dd($user->position->roles[0]->name);
        if ($current_user['role'] == 'admin' && $user->position->roles[0]->name == 'admin') {
            return redirect()->back()->withInput()->with('error', 'Forbidden! Cannot Delete This User');
        }
        if (!$user) {
            abort(404);
        }
        $user->delete(); // Delete user
        MasterManagementService::storeLogActivity('delete-user',$user->id,$user->name);

        return redirect()->route('users.index')->with('success', 'User deleted successfully');
    }

    public function editProfile()
    {
        $user = User::find(AuthService::currentAccess()['id']);
        $positions = Position::all();
        $department = Department::all();
        return view('users.edit-profile', compact('user', 'positions', 'department'));
    }

    /**
     * Update the current user's profile.
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = User::find(AuthService::currentAccess()['id']);


            $request->validate([
                'name' => 'required|string|max:255',
                // 'name_without_degree' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $user->id,
                'password' => 'nullable|string|min:8|confirmed',
                // 'position_id' => 'required|exists:positions,id',
                // 'department_id' => 'nullable|exists:departments,id',
            ]);

            $user->name = $request->name;
            $user->name_without_degree = $request->name_without_degree;
            $user->email = $request->email;
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }
            $user->save();
            MasterManagementService::storeLogActivity('update-profile',$user->id,$user->name);

            return redirect()->route('profile.edit')->with('success', 'Profile updated successfully');
        } catch (\Exception $e) {
            Log::info($e);
            return redirect()->back()->withInput()->with('error', 'Gagal update profile: ' . $e->getMessage());
        }
    }
}
