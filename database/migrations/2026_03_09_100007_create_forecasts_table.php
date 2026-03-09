<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->date('forecast_date');
            $table->decimal('predicted_absenteeism_rate', 5, 2)->nullable();
            $table->unsignedInteger('predicted_absent_count')->nullable();
            $table->string('model_used')->default('holt_winters');
            $table->decimal('confidence_level', 5, 2)->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'forecast_date']);
            $table->index('forecast_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forecasts');
    }
};
