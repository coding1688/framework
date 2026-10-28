<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_schedule_tasks', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 128)->unique();
            $table->json('name_i18n');
            $table->json('description_i18n')->nullable();
            $table->string('command', 128);
            $table->string('cron', 32);
            $table->string('timezone', 64);
            $table->boolean('active')->default(true);
            $table->boolean('without_overlapping')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['active', 'sort']);
        });
        Schema::table('system_schedule_runs', function (Blueprint $table): void {
            $table->foreignId('system_schedule_task_id')->nullable()->after('id')->index();
            $table->string('command', 128)->nullable()->after('task_key');
            $table->longText('output')->nullable()->after('exit_code');
            $table->unsignedInteger('duration_ms')->nullable()->after('finished_at');
        });
    }

    public function down(): void
    {
        Schema::table('system_schedule_runs', function (Blueprint $table): void {
            $table->dropColumn(['system_schedule_task_id', 'command', 'output', 'duration_ms']);
        });
        Schema::dropIfExists('system_schedule_tasks');
    }
};
