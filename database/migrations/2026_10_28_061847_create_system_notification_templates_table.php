<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_notification_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 128)->unique();
            $table->json('name_i18n');
            $table->json('subject_i18n');
            $table->json('content_i18n');
            $table->json('channels');
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_notification_templates');
    }
};
