<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('wallet_id')->constrained('user_wallets')->onDelete('cascade');
            $table->enum('type', ['deposit', 'withdrawal', 'entry_fee', 'prize_credit', 'refund']);
            $table->enum('direction', ['credit', 'debit']);
            $table->decimal('amount', 19, 4);
            $table->string('reference')->unique();
            $table->string('description');
            $table->enum('status', ['pending', 'successful', 'failed'])->default('pending');
            $table->json('meta_data')->nullable();
            $table->timestamps();

            $table->index(['wallet_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
