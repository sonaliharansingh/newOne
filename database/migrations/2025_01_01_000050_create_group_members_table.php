<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_leader')->default(false);
            $table->unsignedBigInteger('related_user_id')->nullable();
            $table->enum('relation_type', [
                'Self', 'Spouse', 'Child', 'Parent', 'Sibling', 'Grandparent', 'Grandchild',
                'Uncle', 'Aunt', 'Cousin', 'Nephew', 'Niece', 'In-law', 'Friend',
            ])->nullable();
            $table->integer('relation_score')->default(0);
            $table->boolean('guardian_required')->default(false);
            $table->integer('allocation_priority')->default(0);
            $table->foreignId('cluster_id')->nullable()->constrained('family_clusters')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['group_id', 'user_id']);
        });

        Schema::table('group_members', function (Blueprint $table) {
            $table->foreign('related_user_id')->references('id')->on('group_members')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('group_members', function (Blueprint $table) {
            $table->dropForeign(['related_user_id']);
        });
        Schema::dropIfExists('group_members');
    }
};
