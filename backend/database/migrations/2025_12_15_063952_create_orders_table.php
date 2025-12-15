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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('symbol', 10);
            $table->string('side', 4);
            $table->decimal('price', 20, 8);
            $table->decimal('amount', 20, 8);
            $table->unsignedTinyInteger('status')->default(1); // 1 open, 2 filled, 3 cancelled
            $table->timestamps();

            $table->index(['symbol', 'status', 'side', 'price', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
