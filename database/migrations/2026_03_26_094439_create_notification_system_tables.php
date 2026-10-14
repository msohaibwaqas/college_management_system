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
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('type')->unique(); // N-01, etc. or a readable name
            $table->string('channel')->default('email'); // email, sms, in-app
            $table->string('subject')->nullable();
            $table->text('body');
            $table->text('placeholders')->nullable(); // JSON list of variables
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');
        });

        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
            $table->foreignId('updated_by')->nullable()->constrained('users');
        });

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->morphs('recipient'); // recipient_id, recipient_type
            $table->string('channel'); // sms, email, in-app
            $table->string('type')->nullable(); // Type reference N-01...
            $table->text('message');
            $table->string('status')->default('pending'); // pending, sent, failed
            $table->integer('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->text('failed_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('recipient_group'); // All Students, Class 11, etc.
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users'); // Sender
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');
        });

        Schema::create('announcement_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained()->cascadeOnDelete();
            $table->morphs('recipient');
            $table->string('channel');
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcement_recipients');
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('notification_settings');
        Schema::dropIfExists('notification_templates');
    }
};
