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
        Schema::table('official_roles', function (Blueprint $table) {
            $table->unsignedTinyInteger('mandatory_response_threshold')->default(5)->after('last_window_close_date');
            $table->unsignedSmallInteger('response_deadline_days')->default(14)->after('mandatory_response_threshold');
            $table->unsignedTinyInteger('participation_quorum_percent')->default(10)->after('response_deadline_days');
            $table->unsignedTinyInteger('response_rejection_downvote_percent')->default(10)->after('participation_quorum_percent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('official_roles', function (Blueprint $table) {
            $table->dropColumn([
                'mandatory_response_threshold',
                'response_deadline_days',
                'participation_quorum_percent',
                'response_rejection_downvote_percent',
            ]);
        });
    }
};
