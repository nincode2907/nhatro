<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('room_id')->unique()->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('rent_amount')->default(0);
            $table->unsignedBigInteger('electricity_unit_price')->default(0);
            $table->unsignedBigInteger('water_unit_price')->default(0);
            $table->unsignedBigInteger('vehicle_amount')->default(0);
            $table->unsignedBigInteger('garbage_amount')->default(0);
            $table->unsignedBigInteger('cable_amount')->default(0);
            $table->unsignedBigInteger('other_amount')->default(0);
            $table->boolean('electricity_enabled')->default(true);
            $table->boolean('water_enabled')->default(true);
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_settings');
    }
};
