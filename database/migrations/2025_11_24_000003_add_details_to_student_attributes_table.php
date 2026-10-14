<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('student_attributes', function (Blueprint $table) {
            $table->string('religion', 50)->nullable()->after('quota_id');
            $table->string('nationality', 50)->nullable()->after('religion');
            $table->string('domicile', 50)->nullable()->after('nationality');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_attributes', function (Blueprint $table) {
            $table->dropColumn(['religion', 'nationality', 'domicile']);
        });
    }
};
