<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_setting_groups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('system_setting_groups')->restrictOnDelete();
            $table->string('code', 64)->unique();
            $table->json('name_i18n');
            $table->json('description_i18n')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
        Schema::create('system_setting_definitions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('group_id')->constrained('system_setting_groups')->restrictOnDelete();
            $table->string('key', 128)->unique();
            $table->json('name_i18n');
            $table->json('description_i18n')->nullable();
            $table->string('input_type', 16);
            $table->boolean('multiple')->default(false);
            $table->string('option_source_type', 32)->default('manual');
            $table->string('option_source_key', 64)->nullable();
            $table->string('depends_on', 128)->nullable();
            $table->string('depends_value', 128)->nullable();
            $table->json('validation')->nullable();
            $table->json('options_i18n')->nullable();
            $table->json('default_value')->nullable();
            $table->boolean('secret')->default(false);
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
        Schema::create('system_setting_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('setting_definition_id')->unique()->constrained('system_setting_definitions')->restrictOnDelete();
            $table->longText('payload');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_setting_values');
        Schema::dropIfExists('system_setting_definitions');
        Schema::dropIfExists('system_setting_groups');
    }
};
