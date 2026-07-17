<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name', 150)->nullable()->after('name');
            $table->string('last_name', 150)->nullable()->after('first_name');
            $table->date('date_of_birth')->nullable()->after('last_name');
            $table->string('language', 50)->nullable()->after('date_of_birth');
            $table->string('passport_number', 50)->nullable()->after('language');
            $table->string('father_name', 150)->nullable()->after('passport_number');
            $table->string('mother_name', 150)->nullable()->after('father_name');
            $table->string('phone', 20)->nullable()->after('mother_name');
            $table->string('city', 100)->nullable()->after('phone');
            $table->string('state', 100)->nullable()->after('city');
            $table->string('country', 100)->nullable()->after('state');
            $table->text('address')->nullable()->after('country');
            $table->integer('age')->nullable()->after('address');
            $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('age');
            $table->string('adhar_id', 12)->nullable()->unique()->after('gender');
            $table->integer('luggage_count')->default(0)->after('adhar_id');
            $table->string('photo_url', 255)->nullable()->after('luggage_count');
            $table->enum('type', ['solo', 'group'])->default('solo')->after('photo_url');
            $table->enum('role', ['admin', 'User', 'Inventorymember'])->default('User')->after('type');
            $table->enum('status', ['active', 'inactive', 'blocked'])->default('active')->after('role');
            $table->timestamp('last_login_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'first_name', 'last_name', 'date_of_birth', 'language', 'passport_number',
                'father_name', 'mother_name', 'phone', 'city', 'state', 'country', 'address',
                'age', 'gender', 'adhar_id', 'luggage_count', 'photo_url', 'type', 'role',
                'status', 'last_login_at',
            ]);
        });
    }
};
