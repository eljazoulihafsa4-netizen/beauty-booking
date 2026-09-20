<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_working_hours', function (Blueprint $table) {
            $table->id();

            $table->foreignId('staff_id')
                ->constrained('staff')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('day_of_week');

            $table->time('open_time')->nullable();
            $table->time('close_time')->nullable();

            $table->boolean('is_closed')->default(false);

            $table->timestamps();

            $table->unique(['staff_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_working_hours');
    }
};