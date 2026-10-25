<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Roles;
use App\Services\UserAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct(
        protected UserAccountService $userAccountService
    ) {}

    /**
     * Display a listing of users
     */
    public function index(Request $request)
    {
        if (!Auth::user()->hasRole('principal') && !Auth::user()->is_super_admin) {
            return redirect()->route('dashboard')->with('error', 'Access denied. This page is only visible to the Principal.');
        }

        $query = User::with(['staff', 'roles']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereHas('staff', function($staffQuery) use ($search) {
                      $staffQuery->where('first_name', 'like', "%{$search}%")
                                 ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user
     */
    public function create()
    {
        $staff = Staff::active()->orderBy('first_name')->get();
        $roles = Roles::all();
        
        return view('users.create', compact('staff', 'roles'));
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        $request->validate([
            'staff_id' => 'required|exists:staff,id|unique:users,staff_id',
            'username' => 'required|string|max:50|unique:users,username',
            'email' => 'required|email|max:120|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'status' => 'required|in:active,inactive,suspended,pending',
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,id',
        ]);

        $user = User::create([
            'staff_id' => $request->staff_id,
            'username' => $request->username,
            'email' => $request->email,
            'password' => $request->password,
            'status' => $request->status,
            'is_super_admin' => $request->has('is_super_admin'),
            'created_by' => Auth::id(),
        ]);

        $user->roles()->attach($request->roles);

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Display the specified user
     */
    public function show(User $user)
    {
        $user->load(['staff', 'roles', 'permissions']);
        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user
     */
    public function edit(User $user)
    {
        $staff = Staff::where(function($q) use ($user) {
            $q->where('status', 'active');
            if ($user->staff_id) {
                $q->orWhere('id', $user->staff_id);
            }
        })->orderBy('first_name')->get();

        $roles = Roles::all();
        return view('users.edit', compact('user', 'staff', 'roles'));
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, User $user)
    {
        $rules = [
            'staff_id' => ['required', 'exists:staff,id', Rule::unique('users')->ignore($user->id)],
            'username' => ['required', 'string', 'max:50', Rule::unique('users')->ignore($user->id)],
            'email' => ['required', 'email', 'max:120', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'status' => 'required|in:active,inactive,suspended,pending',
        ];

        if (Auth::user()->is_super_admin || Auth::user()->hasRole('admin')) {
            $rules['roles'] = 'required|array|min:1';
            $rules['roles.*'] = 'exists:roles,id';
        }

        $request->validate($rules);

        $updateData = [
            'staff_id' => $request->staff_id,
            'username' => $request->username,
            'email' => $request->email,
            'status' => $request->status,
            'updated_by' => Auth::id(),
        ];

        if (Auth::user()->is_super_admin) {
            $updateData['is_super_admin'] = $request->has('is_super_admin');
        }

        $user->update($updateData);

        if (Auth::user()->is_super_admin || Auth::user()->hasRole('admin')) {
            $user->roles()->sync($request->roles);
        }

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user
     */
    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->deleted_by = Auth::id();
        $user->save();
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Create a user account for a student
     */
    public function createAccountForStudent(Student $student)
    {
        $result = $this->userAccountService->createAccountForStudent($student, Auth::id());
        if (!$result['success']) {
            return back()->with('error', $result['error']);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Reset password for a user
     */
    public function resetPassword(User $user)
    {
        $this->userAccountService->resetPassword($user, Auth::id());
        return back()->with('success', "Password for {$user->username} has been reset to '123'. The user will be required to set a new password upon login.");
    }

    /**
     * Bulk create student accounts
     */
    public function bulkCreateStudentAccounts(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
        ]);

        $result = $this->userAccountService->bulkCreateStudentAccounts($request->student_ids, Auth::id());
        return response()->json($result);
    }

    /**
     * Create a user account for a staff member
     */
    public function createAccountForStaff(Staff $staff)
    {
        $result = $this->userAccountService->createAccountForStaff($staff, Auth::id());
        if (!$result['success']) {
            return back()->with('error', $result['error']);
        }

        return back()->with('success', $result['message']);
    }
}
