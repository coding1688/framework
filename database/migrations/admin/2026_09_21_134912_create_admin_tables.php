<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('admin_departments')->restrictOnDelete();
            $table->json('name_i18n');
            $table->string('code', 64)->nullable()->unique();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('admin_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->nullable()->constrained('admin_departments')->restrictOnDelete();
            $table->string('name', 128);
            $table->string('username', 64)->nullable()->unique();
            $table->string('email', 256)->unique();
            $table->string('password', 256);
            $table->string('security_password', 256)->nullable();
            $table->timestamp('security_password_changed_at')->nullable();
            $table->string('locale', 16)->default('en');
            $table->json('preferences')->nullable();
            $table->json('notification_preferences')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->boolean('is_super')->default(false);
            $table->boolean('is_leader')->default(false);
            $table->unsignedInteger('auth_version')->default(1);
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 64)->nullable();
            $table->string('last_login_location', 256)->nullable();
            $table->string('last_login_device', 512)->nullable();
            $table->string('created_ip', 64)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_name', 128)->nullable();
            $table->timestamp('password_changed_at')->nullable();
            $table->string('password_changed_ip', 64)->nullable();
            $table->string('remember_token', 128)->nullable();
            $table->timestamps();
        });
        Schema::create('admin_password_reset_tokens', function (Blueprint $table): void {
            $table->string('email', 256)->primary();
            $table->string('token', 256);
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('admin_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('admin_menus')->restrictOnDelete();
            $table->foreignId('navigation_target_id')->nullable()->constrained('admin_menus')->restrictOnDelete();
            $table->json('context')->nullable();
            $table->json('name_i18n');
            $table->string('type', 16);
            $table->string('permission', 128)->unique();
            $table->json('route_names')->nullable();
            $table->string('path', 256)->nullable();
            $table->string('icon', 64)->nullable();
            $table->string('component', 128)->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('active')->default(true);
            $table->boolean('navigable')->default(true);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });
        Schema::create('admin_department_menu', function (Blueprint $table) {
            $table->foreignId('department_id')->constrained('admin_departments')->cascadeOnDelete();
            $table->foreignId('menu_id')->constrained('admin_menus')->cascadeOnDelete();
            $table->primary(['department_id', 'menu_id']);
        });
        Schema::create('admin_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('administrator_id')->nullable()->index();
            $table->string('actor', 128);
            $table->string('action', 128)->index();
            $table->string('subject', 128)->nullable();
            $table->string('ip', 64)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->json('changes')->nullable();
            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        foreach (['admin_logs', 'admin_department_menu', 'admin_menus', 'admin_users', 'admin_password_reset_tokens', 'admin_departments'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
