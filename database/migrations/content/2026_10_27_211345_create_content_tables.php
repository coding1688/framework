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
    }

    public function down(): void
    {
        Schema::dropIfExists('content_carousels');
        Schema::dropIfExists('content_announcements');
    }
};
