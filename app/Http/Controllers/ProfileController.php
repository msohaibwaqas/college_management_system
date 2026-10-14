<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Models\Designation;
use App\Models\BloodGroup;

use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Show the form for editing the profile.
     */
    public function edit()
    {
        $user = Auth::user();
        $staff = $user->staff;
        $bloodGroups = BloodGroup::all();
        $designations = Designation::orderBy('name')->get();

        return view('profile.edit', compact('user', 'staff', 'bloodGroups', 'designations'));
    }

    /**
     * Update the profile.
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        $staff = $user->staff;

        $rules = [
            'username' => 'required|string|max:50|unique:users,username,' . $user->id,
            'password' => ['nullable', 'string', 'confirmed', Password::min(8)->letters()->numbers()->symbols()],
            'email' => 'required|email|max:120|unique:users,email,' . $user->id,
            'picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];

        if ($staff) {
            $rules = array_merge($rules, [
                'first_name' => 'required|string|max:50',
                'last_name' => 'nullable|string|max:50',
                'father_name' => 'nullable|string|max:50',
                'cnic' => 'nullable|string|max:20',
                'date_of_birth' => 'nullable|date',
                'gender' => 'nullable|in:male,female,other',
                'blood_group_id' => 'nullable|exists:blood_groups,id',
                'contact_number' => 'nullable|string|max:20',
                'whatsapp_number' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:255',
                'designation_id' => 'nullable|exists:designations,id',
                'highest_qualification' => 'nullable|string|max:100',
                'entry_date_govt_service' => 'nullable|date',
                'hed_joining_date' => 'nullable|date',
                'current_college_joining_date' => 'nullable|date',
            ]);
        }

        $request->validate($rules);

        // Update User
        // === DEMO MODE: Password change disabled ===
        // if ($request->filled('password')) {
        //     $user->password = $request->password; // Mutator handles hashing
        // }
        $user->username = $request->username;
        $user->email = $request->email;
        $user->save();

        // Update Staff
        if ($staff) {
            $staff->first_name = $request->first_name;
            $staff->last_name = $request->last_name;
            $staff->father_name = $request->father_name;
            $staff->cnic = $request->cnic;
            $staff->date_of_birth = $request->date_of_birth;
            $staff->gender = $request->gender;
            $staff->blood_group_id = $request->blood_group_id;
            $staff->contact_number = $request->contact_number;
            $staff->whatsapp_number = $request->whatsapp_number;
            $staff->email = $request->email; // Sync email
            $staff->address = $request->address;
            
            if ($request->filled('designation_id')) {
                $staff->designation_id = $request->designation_id;
                // Also update the string field for consistency if it exists
                $designation = Designation::find($request->designation_id);
                if ($designation) {
                    $staff->designation = $designation->name;
                }
            }
            
            $staff->highest_qualification = $request->highest_qualification;
            $staff->entry_date_govt_service = $request->entry_date_govt_service;
            $staff->hed_joining_date = $request->hed_joining_date;
            $staff->current_college_joining_date = $request->current_college_joining_date;

            $staff->save();
        }

        // Handle Picture Upload
        if ($request->hasFile('picture')) {
            $model = $staff ?? $user->student;
            if ($model) {
                // Delete old picture if exists
                if ($model->picture && file_exists(public_path($model->picture))) {
                     @unlink(public_path($model->picture));
                } elseif ($model->picture && Storage::disk('public')->exists($model->picture)) {
                    Storage::disk('public')->delete($model->picture);
                }

                $file = $request->file('picture');
                $type = $staff ? 'staff' : 'student';
                $filename = $type . '_' . $model->id . '_' . time() . '.' . $file->getClientOriginalExtension();
                
                $destinationPath = public_path('uploads/' . $type);
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }
                $file->move($destinationPath, $filename);
                
                $model->picture = 'uploads/' . $type . '/' . $filename;
                $model->save();
            }
        }

        return redirect()->route('profile.edit')->with('success', 'Profile updated successfully.');
    }
}
