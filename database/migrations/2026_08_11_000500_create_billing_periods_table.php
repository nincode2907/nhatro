<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_periods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->string('period_key', 7);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status')->default('OPEN');
            $table->foreignId('finalized_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();

            $table->unique(['property_id', 'period_key']);
            $table->index(['property_id', 'status']);
            $table->index('period_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_periods');
    }
};
