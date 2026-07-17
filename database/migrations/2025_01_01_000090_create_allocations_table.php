<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('cluster_id')->nullable()->constrained('family_clusters')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->foreignId('allocated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('allocation_type', ['auto', 'manual'])->default('auto');
            $table->integer('allocation_score')->default(0);
            $table->integer('priority_level')->nullable();
            $table->enum('allocation_status', ['pending', 'allocated', 'checked_in', 'cancelled'])->default('pending');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('allocations');
    }
};
