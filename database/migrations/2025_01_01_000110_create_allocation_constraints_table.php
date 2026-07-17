<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('allocation_constraints', function (Blueprint $table) {
            $table->id();
            $table->string('constraint_name', 150);
            $table->enum('constraint_type', ['hard', 'soft']);
            $table->integer('weight')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('allocation_constraints');
    }
};
