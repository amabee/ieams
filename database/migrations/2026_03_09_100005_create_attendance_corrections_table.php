<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_record_id')->constrained('attendance_records')->onDelete('cascade');
            $table->foreignId('corrected_by')->constrained('users')->onDelete('cascade');
            $table->time('old_time_in')->nullable();
            $table->time('old_time_out')->nullable();
            $table->string('old_status')->nullable();
            $table->time('new_time_in')->nullable();
            $table->time('new_time_out')->nullable();
            $table->string('new_status')->nullable();
            $table->text('reason');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->enum('status', ['pending', 'approved', 'denied'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_corrections');
    }
};
