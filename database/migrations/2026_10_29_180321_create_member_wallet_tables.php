<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_wallets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('currency', 16);
            $table->unsignedBigInteger('balance_minor')->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'currency']);
        });

        Schema::create('member_wallet_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained('member_wallets')->cascadeOnDelete();
            $table->string('direction', 16);
            $table->string('currency', 16);
            $table->string('type', 32);
            $table->unsignedBigInteger('amount_minor');
            $table->unsignedBigInteger('balance_before_minor');
            $table->unsignedBigInteger('balance_after_minor');
            $table->string('reference', 128)->unique();
            $table->string('remark', 512)->nullable();
            $table->string('actor_type', 16);
            $table->string('actor', 128);
            $table->timestamp('created_at')->index();
            $table->index(['wallet_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('member_wallet_daily_stats', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('stat_date');
            $table->string('currency', 16);
            $table->string('type', 32);
            $table->unsignedBigInteger('credit_amount_minor')->default(0);
            $table->unsignedBigInteger('debit_amount_minor')->default(0);
            $table->unsignedInteger('credit_count')->default(0);
            $table->unsignedInteger('debit_count')->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'stat_date', 'currency', 'type'], 'member_wallet_daily_stats_unique');
            $table->index(['user_id', 'stat_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_wallet_daily_stats');
        Schema::dropIfExists('member_wallet_transactions');
        Schema::dropIfExists('member_wallets');
    }
};
