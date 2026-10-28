<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 32)->unique();
            $table->string('name', 128);
            $table->string('email', 256)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password', 256)->nullable();
            $table->unsignedSmallInteger('vip_level')->default(0)->index();
            $table->string('avatar_url', 512)->nullable();
            $table->boolean('active')->default(true)->index();
            $table->unsignedInteger('auth_version')->default(1);
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 64)->nullable();
            $table->string('last_login_device', 512)->nullable();
            $table->timestamp('password_changed_at')->nullable();
            $table->json('notification_preferences')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('social_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('provider', 32);
            $table->string('provider_id', 128);
            $table->timestamps();
            $table->unique(['provider', 'provider_id']);
            $table->unique(['user_id', 'provider']);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token', 256);
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent');
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
