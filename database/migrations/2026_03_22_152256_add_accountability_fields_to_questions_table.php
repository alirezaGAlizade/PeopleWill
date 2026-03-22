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
        Schema::table('questions', function (Blueprint $table) {
            $table->timestamp('response_deadline_at')->nullable()->after('visits');
            $table->timestamp('response_validation_ends_at')->nullable()->after('response_deadline_at');
            $table->timestamp('second_response_deadline_at')->nullable()->after('response_validation_ends_at');
            $table->timestamp('remediation_review_ends_at')->nullable()->after('second_response_deadline_at');
            $table->timestamp('threshold_met_at')->nullable()->after('remediation_review_ends_at');
            $table->timestamp('second_response_posted_at')->nullable()->after('threshold_met_at');

            $table->index(['status', 'response_deadline_at']);
            $table->index(['status', 'response_validation_ends_at']);
            $table->index(['status', 'second_response_deadline_at']);
            $table->index(['status', 'remediation_review_ends_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex(['status', 'response_deadline_at']);
            $table->dropIndex(['status', 'response_validation_ends_at']);
            $table->dropIndex(['status', 'second_response_deadline_at']);
            $table->dropIndex(['status', 'remediation_review_ends_at']);

            $table->dropColumn([
                'response_deadline_at',
                'response_validation_ends_at',
                'second_response_deadline_at',
                'remediation_review_ends_at',
                'threshold_met_at',
                'second_response_posted_at',
            ]);
        });
    }
};
