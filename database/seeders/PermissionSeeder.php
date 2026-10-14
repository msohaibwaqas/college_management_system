<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Roles;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            // Student Management
            'students.view', 'students.create', 'students.edit', 'students.delete', 'students.import', 'students.trash',
            
            // Staff Management
            'staff.view', 'staff.create', 'staff.edit', 'staff.delete', 'staff.assign_students',
            
            // Academic
            'subject.view', 'subject.manage', 'bise.view', 'bise.manage', 'designations.manage',
            
            // Exams
            'datesheets.manage', 'seating_plans.manage', 'rooms.manage',
            
            // Results
            'results.view', 'results.create', 'results.edit', 'results.final_manage',
            
            // Reports
            'reports.view', 'attendance.reports',
            
            // HE (Higher Education)
            'he.programs.manage', 'he.subjects.manage', 'he.admissions.manage', 'he.results.manage',
            'he.datesheets.manage', 'he.timetables.manage', 'he.attendance.view',
            
            // User Management
            'users.manage',
        ];

        foreach ($permissions as $permissionName) {
            Permission::updateOrCreate(['name' => $permissionName]);
        }

        // Assign some default permissions to roles for demonstration
        $hsscClerk = Roles::where('name', 'hssc_clerk')->first();
        if ($hsscClerk) {
            $hsscClerk->permissions()->sync(Permission::whereIn('name', [
                'students.view', 'students.create', 'students.import', 'students.trash',
                'datesheets.manage', 'seating_plans.manage', 'results.create'
            ])->pluck('id'));
        }

        $bsClerk = Roles::where('name', 'bs_clerk')->first();
        if ($bsClerk) {
            $bsClerk->permissions()->sync(Permission::whereIn('name', [
                'he.admissions.manage', 'he.results.manage', 'he.datesheets.manage', 'he.timetables.manage'
            ])->pluck('id'));
        }
    }
}
