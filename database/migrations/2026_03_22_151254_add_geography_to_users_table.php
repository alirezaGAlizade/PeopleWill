<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedSmallInteger('country_id')->nullable()->after('remember_token');
            $table->unsignedSmallInteger('province_id')->nullable()->after('country_id');
            $table->unsignedSmallInteger('city_id')->nullable()->after('province_id');

            $table->foreign('country_id')->references('id')->on('countries')->nullOnDelete();
            $table->foreign('province_id')->references('id')->on('provinces')->nullOnDelete();
            $table->foreign('city_id')->references('id')->on('cities')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['country_id']);
            $table->dropForeign(['province_id']);
            $table->dropForeign(['city_id']);
            $table->dropColumn(['country_id', 'province_id', 'city_id']);
        });
    }
};
