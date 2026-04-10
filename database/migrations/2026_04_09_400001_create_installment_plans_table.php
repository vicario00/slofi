<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('credit_card_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->decimal('total_amount', 15, 2);
            $table->decimal('installment_amount', 15, 2);
            $table->unsignedInteger('total_installments');
            $table->unsignedInteger('paid_installments')->default(0);
            $table->date('starts_at');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['credit_card_id', 'cancelled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installment_plans');
    }
};
