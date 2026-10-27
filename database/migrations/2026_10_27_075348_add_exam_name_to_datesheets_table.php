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
        Schema::table('datesheets', function (Blueprint $table) {
            $table->string('exam_name', 150)->nullable()->after('subject_id')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('datesheets', function (Blueprint $table) {
            $table->dropIndex(['exam_name']);
            $table->dropColumn('exam_name');
        });
    }
};
