<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Map existing text designations to designation IDs
        $designations = DB::table('designations')->get();
        
        foreach ($designations as $designation) {
            // Update staff records where designation text matches
            DB::table('staff')
                ->where('designation', 'LIKE', '%' . $designation->name . '%')
                ->update(['designation_id' => $designation->id]);
        }
        
        // Also try exact matches (case-insensitive)
        foreach ($designations as $designation) {
            DB::table('staff')
                ->whereRaw('LOWER(designation) = ?', [strtolower($designation->name)])
                ->update(['designation_id' => $designation->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Set all designation_id back to null
        DB::table('staff')->update(['designation_id' => null]);
    }
};
