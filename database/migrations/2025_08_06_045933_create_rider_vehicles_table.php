<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::create('rider_vehicles', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('engine_type');
        $table->string('engine_size');
        $table->string('tire_type')->nullable();
        $table->string('model')->nullable();
        $table->string('front_suspension')->nullable();
        $table->string('rear_suspension')->nullable();
        $table->string('front_sprocket')->nullable();
        $table->string('rear_sprocket')->nullable();
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rider_vehicles');
    }
};
