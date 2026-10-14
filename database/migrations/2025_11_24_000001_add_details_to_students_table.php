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
        Schema::table('students', function (Blueprint $table) {
            $table->enum('gender', ['Male', 'Female', 'Other'])->after('date_of_birth');
            $table->string('cnic', 15)->nullable()->unique()->after('gender');
            $table->string('picture', 255)->nullable()->after('cnic');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['gender', 'cnic', 'picture']);
        });
    }
};
