<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meter_readings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('billing_period_id')->constrained()->restrictOnDelete();
            $table->foreignId('room_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('PENDING');
            $table->unsignedBigInteger('electricity_previous')->nullable();
            $table->unsignedBigInteger('electricity_current')->nullable();
            $table->unsignedBigInteger('electricity_usage')->nullable();
            $table->unsignedBigInteger('water_previous')->nullable();
            $table->unsignedBigInteger('water_current')->nullable();
            $table->unsignedBigInteger('water_usage')->nullable();
            $table->string('skip_reason')->nullable();
            $table->text('note')->nullable();
            $table->boolean('meter_reset')->default(false);
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();

            $table->unique(['billing_period_id', 'room_id']);
            $table->index(['billing_period_id', 'status']);
            $table->index(['room_id', 'billing_period_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meter_readings');
    }
};
