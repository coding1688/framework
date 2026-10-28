<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_vip_levels', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('level')->unique();
            $table->json('name_i18n');
            $table->json('description_i18n')->nullable();
            $table->json('benefits_i18n')->nullable();
            $table->unsignedInteger('duration_days')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
            $table->index(['active', 'sort']);
        });
        Schema::create('member_vip_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('from_vip_level_id')->nullable()->constrained('member_vip_levels')->restrictOnDelete();
            $table->foreignId('to_vip_level_id')->nullable()->constrained('member_vip_levels')->restrictOnDelete();
            $table->timestamp('from_expires_at')->nullable();
            $table->timestamp('to_expires_at')->nullable();
            $table->timestamp('started_at');
            $table->foreignId('administrator_id')->nullable();
            $table->string('reason', 512)->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('vip_level_id')->nullable()->after('vip_level')->index();
            $table->timestamp('vip_started_at')->nullable()->after('vip_level_id');
            $table->timestamp('vip_expires_at')->nullable()->after('vip_started_at')->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['vip_level_id', 'vip_started_at', 'vip_expires_at']);
        });
        Schema::dropIfExists('member_vip_records');
        Schema::dropIfExists('member_vip_levels');
    }
};
