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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            $table->string('stripe_product_id')->nullable();
            $table->string('stripe_price_id')->nullable();

            $table->integer('price')->default(0); // price in cents
            $table->string('currency', 3)->default('usd');
            $table->string('interval')->default('month'); // month, year, etc.
            $table->integer('trial_days')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
