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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
              $table->unsignedBigInteger('user_id');
        $table->string('razorpay_payment_id');
        $table->string('razorpay_order_id');
        $table->decimal('usd_amount', 10, 2);
        $table->decimal('inr_amount', 10, 2);
        $table->decimal('exchange_rate', 10, 2);
        $table->string('status')->default('paid');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
