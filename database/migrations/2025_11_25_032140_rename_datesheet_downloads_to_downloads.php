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
        // Rename the table
        Schema::rename('datesheet_downloads', 'downloads');
        
        // Add new columns
        Schema::table('downloads', function (Blueprint $table) {
            $table->foreignId('uploaded_by')->nullable()->after('published_date')->constrained('users')->nullOnDelete();
            $table->integer('file_size')->nullable()->after('file_path')->comment('File size in bytes');
            $table->integer('download_count')->default(0)->after('file_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove added columns
        Schema::table('downloads', function (Blueprint $table) {
            $table->dropForeign(['uploaded_by']);
            $table->dropColumn(['uploaded_by', 'file_size', 'download_count']);
        });
        
        // Rename back to original
        Schema::rename('downloads', 'datesheet_downloads');
    }
};
