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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('email')->unique();
            $table->string('otp')->nullable();
            $table->string('bio')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->timestamp('otp_verified_at')->nullable();
            $table->longText('reset_password_token')->nullable();
            $table->timestamp('reset_password_token_expire_at')->nullable();
            $table->string('avatar')->nullable();
            $table->string('password');
            $table->timestamp('last_activity_at')->nullable();
            $table->string('stripe_customer_id')->nullable();
            $table->string('stripe_account_id')->nullable();
            $table->string('stripe_subscription_id')->nullable();
            $table->text('address')->nullable();
            $table->decimal('longitude', 10, 7)->nullable()->comment('Geographic longitude');
            $table->decimal('latitude', 10, 7)->nullable()->comment('Geographic latitude');
            $table->string('rider_type')->nullable()->comment('Type of rider: bike, car, truck, etc.');
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
