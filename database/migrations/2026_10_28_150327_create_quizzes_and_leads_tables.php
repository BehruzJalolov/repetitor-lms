<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->text('question');
            $table->string('option_a');
            $table->string('option_b');
            $table->string('option_c');
            $table->string('option_d');
            $table->enum('correct_option', ['a', 'b', 'c', 'd'])->default('a');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::table('lesson_user', function (Blueprint $table) {
            $table->unsignedTinyInteger('quiz_score')->nullable()->after('is_completed');
            $table->boolean('quiz_passed')->default(false)->after('quiz_score');
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone');
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->string('message')->nullable();
            $table->enum('status', ['new', 'contacted', 'enrolled'])->default('new');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');

        Schema::table('lesson_user', function (Blueprint $table) {
            $table->dropColumn(['quiz_score', 'quiz_passed']);
        });

        Schema::dropIfExists('lesson_questions');
    }
};
