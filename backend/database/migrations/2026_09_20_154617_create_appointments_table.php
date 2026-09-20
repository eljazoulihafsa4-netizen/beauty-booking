<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('business_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('staff_id')
                ->constrained('staff')
                ->cascadeOnDelete();

            $table->foreignId('service_id')
                ->constrained('services')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->date('appointment_date');
            $table->time('start_time');
            $table->time('end_time');

            $table->enum('status', [
                'pending',
                'payment_pending',
                'confirmed',
                'cancelled',
                'completed',
                'no_show',
            ])->default('pending');

            $table->enum('payment_status', [
                'pending',
                'paid',
                'failed',
                'refunded',
            ])->default('pending');

            $table->decimal('price', 10, 2);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index([
                'business_id',
                'appointment_date',
                'staff_id',
            ]);

            $table->index([
                'business_id',
                'appointment_date',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};