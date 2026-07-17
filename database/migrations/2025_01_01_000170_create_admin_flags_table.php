<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('cluster_id')->nullable()->constrained('family_clusters')->nullOnDelete();
            $table->foreignId('member_id')->constrained('group_members')->cascadeOnDelete();
            $table->enum('flag_type', ['missing_female_guardian', 'missing_male_guardian']);
            $table->enum('status', ['open', 'resolved'])->default('open');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_flags');
    }
};
