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
        Schema::create('posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('challenge_id')->constrained()->cascadeOnDelete();
            $table->text('caption')->nullable();
            $table->string('media_url');
            $table->enum('media_type', ['image', 'video']);
            $table->string('thumbnail_url')->nullable();
            $table->enum('status', ['pending', 'published', 'rejected'])->default('pending');
            $table->bigInteger('likes_count')->default(0);
            $table->bigInteger('comments_count')->default(0);
            $table->bigInteger('views_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['challenge_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
