<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Roles;
use App\Models\Permission;

class RolePermissionController extends Controller
{
    public function index()
    {
        $roles = Roles::with('permissions')->get();
        $permissions = Permission::all();
        return view('admin.roles.permissions', compact('roles', 'permissions'));
    }

    public function update(Request $request)
    {
        $rolePermissions = $request->input('role_permissions', []);

        $roles = Roles::all();

        foreach ($roles as $role) {
            // If the role is in the input, use those permissions, otherwise sync with empty array
            $permissionIds = isset($rolePermissions[$role->id]) ? $rolePermissions[$role->id] : [];
            $role->permissions()->sync($permissionIds);
        }

        return back()->with('success', 'Permissions updated successfully.');
    }
}
