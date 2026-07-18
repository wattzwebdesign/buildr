<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buildr_pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('buildr_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('buildr_pages')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('buildr_nodes')->cascadeOnDelete();
            $table->string('type');
            $table->unsignedInteger('sort')->default(0);
            $table->json('data')->nullable();
            $table->boolean('visible')->default(true);
            $table->timestamps();

            $table->index(['page_id', 'parent_id', 'sort']);
        });

        Schema::create('buildr_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        Schema::create('buildr_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('buildr_pages')->cascadeOnDelete();
            $table->json('snapshot');
            $table->string('label')->nullable();
            $table->timestamps();
        });

        Schema::create('buildr_form_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->nullable()->constrained('buildr_pages')->nullOnDelete();
            $table->string('form_key')->nullable();
            $table->json('payload');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buildr_form_submissions');
        Schema::dropIfExists('buildr_revisions');
        Schema::dropIfExists('buildr_settings');
        Schema::dropIfExists('buildr_nodes');
        Schema::dropIfExists('buildr_pages');
    }
};
