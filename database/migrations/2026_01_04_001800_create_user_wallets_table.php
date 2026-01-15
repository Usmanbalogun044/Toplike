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
        Schema::create('user_wallets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('balance', 19, 4)->default(0);
            $table->string('currency')->default('NGN');
            $table->string('withdrawal_pin')->nullable();
            $table->boolean('is_frozen')->default(false);
            $table->string('paystack_customer_code')->nullable()->index();
            $table->string('paystack_dedicated_account_id')->nullable()->index();
            $table->string('virtual_account_number')->nullable();
            $table->string('virtual_account_bank_name')->nullable();
            $table->string('virtual_account_bank_code')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_wallets');
    }
};
