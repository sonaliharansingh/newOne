<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->foreignId('floor_id')->constrained('floors')->cascadeOnDelete();
            $table->string('room_number', 50);
            $table->enum('room_type', ['single', 'double', 'triple', 'quad', 'dormitory']);
            $table->integer('capacity');
            $table->integer('occupied_count')->default(0);
            $table->integer('available_count')->default(0);
            $table->boolean('is_private')->default(false);
            $table->boolean('lift_access')->default(false);
            $table->boolean('staircase_access')->default(true);
            $table->boolean('women_only')->default(false);
            $table->boolean('elderly_friendly')->default(false);
            $table->enum('room_status', ['available', 'partial', 'full', 'maintenance'])->default('available');
            $table->timestamps();

            $table->unique(['hotel_id', 'room_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
