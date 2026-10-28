<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('common_regions', function (Blueprint $table) {
            $table->id();
            $table->char('country_code', 2)->index();
            $table->string('code', 16)->unique();
            $table->json('name_i18n');
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('common_provinces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained('common_regions')->cascadeOnDelete();
            $table->string('code', 16)->unique();
            $table->string('type', 16)->default('province')->index();
            $table->json('name_i18n');
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('common_cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained('common_regions')->cascadeOnDelete();
            $table->foreignId('province_id')->constrained('common_provinces')->cascadeOnDelete();
            $table->string('code', 16)->unique();
            $table->string('type', 32)->index();
            $table->json('name_i18n');
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('common_cities');
        Schema::dropIfExists('common_provinces');
        Schema::dropIfExists('common_regions');
    }
};
