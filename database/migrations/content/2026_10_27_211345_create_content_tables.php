<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_announcements', function (Blueprint $table): void {
            $table->id();
            $table->json('title_i18n');
            $table->json('content_i18n');
            $table->timestamp('published_at')->nullable()->index();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('content_carousels', function (Blueprint $table): void {
            $table->id();
            $table->json('title_i18n');
            $table->foreignId('image_id')->constrained('files')->restrictOnDelete();
            $table->string('link_url', 512)->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('content_document_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 32)->index();
            $table->foreignId('parent_id')->nullable()->constrained('content_document_categories')->restrictOnDelete();
            $table->string('slug', 128);
            $table->json('name_i18n');
            $table->json('description_i18n')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
            $table->unique(['type', 'slug']);
        });

        Schema::create('content_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained('content_document_categories')->restrictOnDelete();
            $table->string('type', 32)->index();
            $table->string('slug', 128);
            $table->json('title_i18n');
            $table->json('summary_i18n')->nullable();
            $table->json('content_i18n');
            $table->timestamp('published_at')->nullable()->index();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
            $table->unique(['type', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_documents');
        Schema::dropIfExists('content_document_categories');
        Schema::dropIfExists('content_carousels');
        Schema::dropIfExists('content_announcements');
    }
};
