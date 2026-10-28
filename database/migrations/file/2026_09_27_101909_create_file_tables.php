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
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->string('disk', 32)->index();
            $table->string('type', 32)->default('general')->index();
            $table->string('path', 512);
            $table->string('original_name', 256);
            $table->string('extension', 16)->nullable();
            $table->string('mime_type', 128);
            $table->unsignedBigInteger('size');
            $table->string('visibility', 16)->default('public');
            $table->char('checksum', 64)->index();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['disk', 'path']);
        });

        Schema::create('file_references', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('file_id')->constrained('files')->restrictOnDelete();
            $table->string('reference_type', 128);
            $table->unsignedBigInteger('reference_id');
            $table->string('field', 128);
            $table->timestamps();
            $table->index(['reference_type', 'reference_id']);
            $table->unique(['file_id', 'reference_type', 'reference_id', 'field'], 'file_reference_unique');
        });

        Schema::create('file_configurations', function (Blueprint $table): void {
            $table->id();
            $table->string('default_disk', 32)->nullable();
            $table->unsignedInteger('max_upload_mb')->default(32);
            $table->json('allowed_extensions');
            $table->timestamps();
        });
        Schema::create('file_storages', function (Blueprint $table): void {
            $table->id();
            $table->string('disk', 32)->unique();
            $table->json('name_i18n');
            $table->string('provider', 32);
            $table->string('visibility', 16)->default('private');
            $table->boolean('enabled')->default(false);
            $table->json('configuration');
            $table->text('credentials')->nullable();
            $table->timestamps();
        });
        Schema::create('file_replicas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('file_id')->constrained('files')->cascadeOnDelete();
            $table->string('disk', 32);
            $table->string('path', 512);
            $table->char('checksum', 64);
            $table->timestamps();
            $table->unique(['file_id', 'disk']);
            $table->unique(['disk', 'path']);
        });
        Schema::create('file_sync_tasks', function (Blueprint $table): void {
            $table->id();
            $table->string('source_disk', 32);
            $table->string('target_disk', 32);
            $table->string('status', 16)->default('pending')->index();
            $table->unsignedBigInteger('last_file_id')->default(0);
            $table->unsignedBigInteger('max_file_id')->default(0);
            $table->unsignedBigInteger('total')->default(0);
            $table->unsignedBigInteger('copied')->default(0);
            $table->unsignedBigInteger('skipped')->default(0);
            $table->unsignedBigInteger('failed')->default(0);
            $table->json('errors')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_sync_tasks');
        Schema::dropIfExists('file_replicas');
        Schema::dropIfExists('file_storages');
        Schema::dropIfExists('file_configurations');
        Schema::dropIfExists('file_references');
        Schema::dropIfExists('files');
    }
};
