<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_schedule_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('task_key', 128)->index();
            $table->string('trigger', 16);
            $table->foreignId('administrator_id')->nullable();
            $table->string('status', 16)->index();
            $table->integer('exit_code')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->index(['task_key', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_schedule_runs');
    }
};
