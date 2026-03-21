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
        Schema::create('countries', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->unsignedSmallInteger('capital_city')->nullable();
            $table->string('name');
            $table->string('name_en');
        });

        Schema::create('provinces', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->unsignedSmallInteger('country');
            $table->string('name');
            $table->string('name_en');
            $table->foreign('country')->references('id')->on('countries');
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->unsignedSmallInteger('province');
            $table->string('name');
            $table->string('name_en');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->foreign('province')->references('id')->on('provinces');
        });

        Schema::table('countries', function (Blueprint $table) {
            $table->foreign('capital_city')->references('id')->on('cities');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropForeign(['capital_city']);
        });

        Schema::dropIfExists('cities');
        Schema::dropIfExists('provinces');
        Schema::dropIfExists('countries');
    }
};
