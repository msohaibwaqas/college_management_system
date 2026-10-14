<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_urdu')->nullable();
            $table->timestamps();
        });

        Schema::create('genres', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_urdu')->nullable();
            $table->timestamps();
        });

        Schema::create('book_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('title_urdu')->nullable();
            $table->string('ref_no')->nullable()->index();
            $table->string('language')->default('English');
            $table->foreignId('author_id')->nullable()->constrained('authors')->nullOnDelete();
            $table->string('book_type')->nullable();
            $table->foreignId('genre_id')->nullable()->constrained('genres')->nullOnDelete();
            $table->string('isbn')->nullable()->index();
            $table->integer('pages')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->string('acc_no')->nullable()->index();
            $table->foreignId('location_id')->nullable()->constrained('book_locations')->nullOnDelete();
            $table->integer('volumes')->default(1);
            $table->year('publication_year')->nullable();
            $table->text('remarks')->nullable();
            $table->enum('status', ['available', 'unavailable'])->default('available');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('book_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('books')->cascadeOnDelete();
            $table->string('issued_to_type'); // student or staff
            $table->unsignedBigInteger('issued_to_id');
            $table->foreignId('issued_by')->constrained('users');
            $table->datetime('issued_at');
            $table->date('due_date');
            $table->datetime('returned_at')->nullable();
            $table->unsignedBigInteger('return_accepted_by')->nullable();
            $table->decimal('fine_amount', 8, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->enum('status', ['issued', 'returned', 'overdue'])->default('issued');
            $table->timestamps();

            $table->index(['issued_to_type', 'issued_to_id']);
            $table->foreign('return_accepted_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_issues');
        Schema::dropIfExists('books');
        Schema::dropIfExists('book_locations');
        Schema::dropIfExists('genres');
        Schema::dropIfExists('authors');
    }
};
