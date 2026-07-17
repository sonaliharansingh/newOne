<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('floors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->integer('floor_number');
            $table->boolean('lift_access')->default(false);
            $table->boolean('staircase_access')->default(true);
            $table->boolean('women_only')->default(false);
            $table->boolean('elderly_friendly')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('floors');
    }
};
