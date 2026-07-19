<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            // Runtime lock: stamped with the gender of a shared room's first occupant while it
            // is partial, cleared back to null once the room empties. Distinct from the
            // permanent, hotel-declared women_only flag. Effective lock = women_only ? female : gender_lock.
            $table->enum('gender_lock', ['male', 'female', 'other'])->nullable()->after('women_only');
            // Cluster-lock: when a family/couple takes a room (private or split), the room is
            // reserved to that cluster so no unrelated pilgrim fills the spare beds. Cleared on reset.
            $table->foreignId('reserved_for_cluster_id')->nullable()->after('gender_lock')
                ->constrained('family_clusters')->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            // Locality tier between city and language for proximity-based bed steering
            // (city -> area -> language). city and language already exist.
            $table->string('area', 100)->nullable()->after('city');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reserved_for_cluster_id');
            $table->dropColumn('gender_lock');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('area');
        });
    }
};
