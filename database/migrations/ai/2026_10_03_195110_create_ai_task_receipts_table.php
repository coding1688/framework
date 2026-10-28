<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_task_receipts', function (Blueprint $table): void {
            $table->uuid('task_id')->primary();
            $table->string('scope', 16);
            $table->string('owner_id', 128);
            $table->string('resource_type', 128);
            $table->string('resource_id', 128);
            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_task_receipts');
    }
};
