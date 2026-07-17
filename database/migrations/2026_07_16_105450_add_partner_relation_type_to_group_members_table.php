<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('group_members', function (Blueprint $table) {
            $table->enum('relation_type', [
                'Self', 'Spouse', 'Partner', 'Child', 'Parent', 'Sibling', 'Grandparent', 'Grandchild',
                'Uncle', 'Aunt', 'Cousin', 'Nephew', 'Niece', 'In-law', 'Friend',
            ])->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('group_members', function (Blueprint $table) {
            $table->enum('relation_type', [
                'Self', 'Spouse', 'Child', 'Parent', 'Sibling', 'Grandparent', 'Grandchild',
                'Uncle', 'Aunt', 'Cousin', 'Nephew', 'Niece', 'In-law', 'Friend',
            ])->nullable()->change();
        });
    }
};
