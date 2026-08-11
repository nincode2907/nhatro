<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->string('type');
            $table->string('description');
            $table->unsignedBigInteger('quantity');
            $table->string('unit', 30);
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('amount');
            $table->json('metadata')->nullable();
            $table->unsignedSmallInteger('sort_order');
            $table->timestamps();

            $table->unique(['invoice_id', 'type']);
            $table->index(['invoice_id', 'sort_order']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
