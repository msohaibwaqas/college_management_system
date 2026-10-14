<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('principals', function (Blueprint $table) {
            $table->id();
            $table->string('title', 20);
            $table->string('name', 100);
            $table->string('gender', 10);
            $table->string('father_name', 100);
            $table->string('designation', 50);
            $table->date('from_date');
            $table->date('to_date')->nullable();
            $table->string('details')->nullable();
            $table->string('image', 50)->nullable();
            $table->boolean('is_current')->default(0);
            $table->foreignId('staff_id')->nullable()->constrained('staff')->onDelete('set null');
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('principals');
    }
};
