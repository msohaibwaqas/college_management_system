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
        Schema::table('staff', function (Blueprint $table) {
            $table->enum('marital_status', ['Single', 'Married', 'Divorced', 'Widowed'])->nullable()->after('gender');
            $table->string('religion', 50)->nullable()->after('marital_status');
            $table->string('nationality', 50)->nullable()->after('religion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn(['marital_status', 'religion', 'nationality']);
        });
    }
};
