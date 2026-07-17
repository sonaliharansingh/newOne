<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_clusters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->string('cluster_name', 150)->nullable();
            $table->integer('cluster_size')->default(0);
            $table->integer('cluster_score')->default(0);
            $table->enum('allocation_status', ['pending', 'partial', 'allocated'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_clusters');
    }
};
