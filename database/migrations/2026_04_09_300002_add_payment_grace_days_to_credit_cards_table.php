<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_cards', function (Blueprint $table) {
            $table->tinyInteger('payment_grace_days')->unsigned()->after('cutoff_day')->default(20);
            $table->dropColumn('payment_day');
        });
    }

    public function down(): void
    {
        Schema::table('credit_cards', function (Blueprint $table) {
            $table->tinyInteger('payment_day')->unsigned()->after('cutoff_day')->default(20);
            $table->dropColumn('payment_grace_days');
        });
    }
};
