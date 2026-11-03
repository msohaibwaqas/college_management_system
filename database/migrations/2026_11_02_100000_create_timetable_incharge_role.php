<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create timetable_incharge role if it doesn't exist
        $role = DB::table('roles')->where('name', 'timetable_incharge')->first();
        if (!$role) {
            $roleId = DB::table('roles')->insertGetId([
                'name' => 'timetable_incharge',
                'description' => 'Timetable Incharge - manages academic class timetables, time slots, and schedules',
                'guard_name' => 'web',
                'is_system_role' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $roleId = $role->id;
        }

        // 2. Ensure permissions exist
        $permissions = [
            'timetables.manage' => 'Manage all timetable entries, matrix, and batch assignments',
            'timetables.view'   => 'View timetable schedules and time slots',
            'he.timetables.manage' => 'Manage higher education (BS/ADP) timetables',
            'rooms.manage'      => 'Manage rooms and blocks for lectures and timetables',
        ];

        foreach ($permissions as $permName => $permDesc) {
            $existingPerm = DB::table('permissions')->where('name', $permName)->first();
            if (!$existingPerm) {
                $permId = DB::table('permissions')->insertGetId([
                    'name' => $permName,
                    'description' => $permDesc,
                    'guard_name' => 'web',
                    'category' => 'Timetable',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $permId = $existingPerm->id;
            }

            // 3. Attach permission to timetable_incharge role
            if (!DB::table('role_permissions')->where('role_id', $roleId)->where('permission_id', $permId)->exists()) {
                DB::table('role_permissions')->insert([
                    'role_id' => $roleId,
                    'permission_id' => $permId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $role = DB::table('roles')->where('name', 'timetable_incharge')->first();
        if ($role) {
            DB::table('role_permissions')->where('role_id', $role->id)->delete();
            DB::table('user_roles')->where('role_id', $role->id)->delete();
            DB::table('roles')->where('id', $role->id)->delete();
        }

        DB::table('permissions')->whereIn('name', [
            'timetables.manage',
            'timetables.view',
        ])->delete();
    }
};
