<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            $table->text('content');
            $table->string('image_url', 1024)->nullable();
            $table->float('authenticity_score')->default(1.0)->index();
            $table->unsignedInteger('view_count')->default(0)->index();
            $table->text('embedding')->nullable();
            $table->timestamps();
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS vector;');
            DB::statement('ALTER TABLE posts DROP COLUMN embedding;');
            DB::statement('ALTER TABLE posts ADD COLUMN embedding vector(1536);');
            try {
                DB::statement('CREATE INDEX idx_posts_embedding_hnsw ON posts USING hnsw (embedding vector_cosine_ops);');
            } catch (\Exception $e) {
                // Ignore if vector_cosine_ops index creation requires more rows or specific settings
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
