<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('youtube_url')->nullable()->change();
            $table->string('video_path')->nullable()->after('youtube_url');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_chat_banned')->default(false)->after('role');
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('chat_messages')->nullOnDelete();
            $table->text('message');
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();

            $table->index(['course_id', 'created_at']);
            $table->index(['lesson_id', 'created_at']);
        });

        Schema::create('live_streams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('stream_type', 20)->default('jitsi'); // jitsi | youtube
            $table->string('room_code', 64)->unique();
            $table->string('youtube_url')->nullable();
            $table->string('status', 20)->default('scheduled'); // scheduled | live | ended
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_streams');
        Schema::dropIfExists('chat_messages');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_chat_banned');
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn('video_path');
        });
    }
};
