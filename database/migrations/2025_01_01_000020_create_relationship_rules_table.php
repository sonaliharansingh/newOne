<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('relationship_rules', function (Blueprint $table) {
            $table->id();
            $table->string('relation_type', 50)->unique();
            $table->integer('score')->default(0);
            $table->boolean('must_stay_together')->default(false);
            $table->boolean('guardian_allowed')->default(false);
            $table->integer('nearby_room_priority')->default(0);
            $table->integer('same_room_priority')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('relationship_rules');
    }
};
