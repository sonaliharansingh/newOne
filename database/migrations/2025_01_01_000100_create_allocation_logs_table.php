<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('allocation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('allocation_id')->constrained('allocations')->cascadeOnDelete();
            $table->string('action', 100);
            $table->foreignId('old_room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->foreignId('new_room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('allocation_logs');
    }
};
