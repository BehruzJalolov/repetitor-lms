<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('stored_media')) {
            Schema::create('stored_media', function (Blueprint $table) {
                $table->id();
                $table->string('disk', 30)->default('public');
                $table->string('path')->unique();
                $table->string('mime_type', 100)->default('image/jpeg');
                $table->unsignedInteger('size')->default(0);
                $table->longText('contents');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stored_media');
    }
};
