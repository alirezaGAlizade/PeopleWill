<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('official_roles', function (Blueprint $table) {
            $table->string('window_plan')->nullable()->after('city_id');
            $table->unsignedSmallInteger('open_window_duration')->nullable()->after('window_plan');
            $table->timestamp('last_window_close_date')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'))->after('open_window_duration');
        });

        DB::table('official_roles')
            ->whereNull('last_window_close_date')
            ->update(['last_window_close_date' => now()]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('official_roles', function (Blueprint $table) {
            $table->dropColumn([
                'window_plan',
                'open_window_duration',
                'last_window_close_date',
            ]);
        });
    }
};
