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
        Schema::create('challenges', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('week_number');
            $table->year('year');
            $table->decimal('entry_fee', 10, 2)->default(500);
            $table->decimal('total_pool', 12, 2)->default(0);
            $table->timestamp('starts_at')->nullable();  
            $table->timestamp('ends_at')->nullable();    
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('challenges');
    }
};
