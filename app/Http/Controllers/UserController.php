<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of users
     */
    public function index(Request $request)
    {
        // Restrict access to Principal only (and Super Admin)
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
            'password' => $request->password, // Will be hashed by mutator
            'status' => $request->status,
            'is_super_admin' => $request->has('is_super_admin'),
            'created_by' => Auth::id(),
        ]);

        // Attach roles
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
        // Include active staff members AND the user's currently assigned staff member (even if inactive/left)
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

        // === DEMO MODE: Password change disabled ===
        // Only update password if provided
        // if ($request->filled('password')) {
        //     $updateData['password'] = $request->password;
        // }

        $user->update($updateData);

        $user->update($updateData);

        // Sync roles only if current user is admin or super admin
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
        // Prevent deleting yourself
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        // Soft delete
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
        // Check if user already exists (including trashed)
        $user = User::withTrashed()->where('student_id', $student->id)->first();
        
        if ($user && !$user->trashed()) {
            return back()->with('error', 'This student already has an active user account.');
        }

        $admission = $student->admissions->first();
        if (!$admission || !$admission->college_roll_no) {
            return back()->with('error', 'Student must have a college roll number to create an account.');
        }

        // Determine account status based on admission status
        $accountStatus = ($admission->status === 'Active' || $admission->status === 'Admitted') ? 'active' : 'suspended';

        // Role 'student'
        $role = Roles::where('name', 'student')->first();
        if (!$role) {
            return back()->with('error', "Role 'student' not found. Please run the roles seeder.");
        }

        // Create username from college_roll_no
        $username = strtolower(str_replace(' ', '', $admission->college_roll_no));
        // Use student email or fallback to rollno@rsclosetor.com
        $email = $student->contact->email ?? $username . '@rsclosetor.com';

        // Check if username/email conflicts with another user
        $conflict = User::withTrashed()
            ->where(function($q) use ($username, $email) {
                $q->where('username', $username)
                  ->orWhere('email', $email);
            })
            ->where('student_id', '!=', $student->id)
            ->first();
            
        if ($conflict) {
            return back()->with('error', "Conflict detected: Username or Email is already in use by another account.");
        }

        if ($user) {
            // Restore and update existing trashed user
            $user->restore();
            $user->update([
                'username' => $username,
                'email' => $email,
                'password' => '123',
                'status' => $accountStatus,
                'updated_by' => Auth::id(),
            ]);
            
            $user->roles()->sync([$role->id]);
            $message = "Student user account restored successfully!";
        } else {
            // Create new user account
            $user = User::create([
                'student_id' => $student->id,
                'username' => $username,
                'email' => $email,
                'password' => '123',
                'status' => $accountStatus,
                'is_super_admin' => false,
                'created_by' => Auth::id(),
            ]);

            $user->roles()->attach($role->id);
            $message = "Student user account created successfully!";
        }

        return back()->with('success', "{$message} Username: {$username}, Password: 123");
    }

    /**
     * Reset password for a user
     */
    public function resetPassword(User $user)
    {
        $user->update([
            'password' => '123',
            'must_change_password' => true,
            'password_changed_at' => null,
            'updated_by' => Auth::id(),
        ]);

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

        $students = Student::with(['admissions', 'heAdmissions', 'contact', 'user'])->findMany($request->student_ids);
        $role = Roles::where('name', 'student')->first();
        
        if (!$role) {
            return response()->json(['success' => false, 'message' => "Student role not found."]);
        }

        $created = 0;
        $errors = [];

        foreach ($students as $student) {
            if ($student->user) continue;

            $rollNo = $student->admissions->first()?->college_roll_no ?? $student->heAdmissions->first()?->roll_no;
            if (!$rollNo) {
                $errors[] = "Student {$student->id} lacks a roll number.";
                continue;
            }

            $username = strtolower(str_replace(' ', '', $rollNo));
            $email = $student->contact->email ?? $username . '@rsclosetor.com';

            // Simple conflict check
            if (User::where('username', $username)->orWhere('email', $email)->exists()) {
                $errors[] = "Conflict for student {$username}.";
                continue;
            }

            $user = User::create([
                'student_id' => $student->id,
                'username' => $username,
                'email' => $email,
                'password' => '123',
                'status' => 'active',
                'created_by' => Auth::id(),
            ]);

            $user->roles()->attach($role->id);
            $created++;
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully created {$created} student accounts." . (count($errors) > 0 ? " Errors: " . implode(', ', $errors) : ""),
        ]);
    }

    /**
     * Create a user account for a staff member
     */
    public function createAccountForStaff(Staff $staff)
    {
        // Check if user already exists (including trashed)
        $user = User::withTrashed()->where('staff_id', $staff->id)->first();
        
        if ($user && !$user->trashed()) {
            return back()->with('error', 'This staff member already has an active user account.');
        }

        // Determine role based on category
        $roleName = ($staff->category === 'teaching') ? 'teacher' : 'clerk';
        $role = Roles::where('name', $roleName)->first();
        
        if (!$role) {
            // Fallback to any sensible role or return error
            $role = Roles::where('name', 'teacher')->first() ?: Roles::where('name', 'clerk')->first();
            if (!$role) {
                return back()->with('error', "Default roles ('teacher' or 'clerk') not found. Please run the roles seeder.");
            }
        }

        // Create username from staff_id or first_name + id
        $username = $staff->staff_id ? strtolower(str_replace(' ', '', $staff->staff_id)) : strtolower(str_replace(' ', '', $staff->first_name . $staff->id));
        
        // Use staff email or fallback
        $email = $staff->email ?? $username . '@rsclosetor.com';

        // Check for conflicts
        $conflict = User::withTrashed()
            ->where(function($q) use ($username, $email) {
                $q->where('username', $username)
                  ->orWhere('email', $email);
            })
            ->where('staff_id', '!=', $staff->id)
            ->first();
            
        if ($conflict) {
            return back()->with('error', "Conflict detected: Username or Email is already in use by another account.");
        }

        if ($user) {
            $user->restore();
            $user->update([
                'username' => $username,
                'email' => $email,
                'password' => '123',
                'status' => 'active',
                'updated_by' => Auth::id(),
            ]);
            $user->roles()->sync([$role->id]);
            $message = "Staff user account restored successfully!";
        } else {
            $user = User::create([
                'staff_id' => $staff->id,
                'username' => $username,
                'email' => $email,
                'password' => '123',
                'status' => 'active',
                'is_super_admin' => false,
                'created_by' => Auth::id(),
            ]);
            $user->roles()->attach($role->id);
            $message = "Staff user account created successfully!";
        }

        return back()->with('success', "{$message} Username: {$username}, Password: 12345678A@");
    }
}
