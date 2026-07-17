<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('group_name', 150);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->integer('expected_members')->default(1);
            $table->integer('joined_members')->default(0);
            $table->string('invite_code', 100)->nullable()->unique();
            $table->timestamp('invite_expiry')->nullable();
            $table->enum('status', ['open', 'closed', 'allocated', 'cancelled'])->default('open');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
