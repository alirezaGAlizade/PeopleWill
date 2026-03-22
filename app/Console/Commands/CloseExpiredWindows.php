<?php

namespace App\Console\Commands;

use App\Enums\WindowPlan;
use App\Models\OfficialRole;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:close-expired-windows')]
#[Description('Close official role windows that passed their open duration.')]
class CloseExpiredWindows extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = now()->toImmutable();
        $updatedCount = 0;

        $candidates = OfficialRole::query()
            ->whereNot('window_plan', WindowPlan::Continuously->value)
            ->whereNotNull('window_plan')
            ->whereNotNull('open_window_duration')
            ->whereNotNull('last_window_close_date')
            ->lazyById();

        foreach ($candidates as $officialRole) {
            $windowClosesAt = $officialRole->windowClosesAt();

            if ($windowClosesAt === null || $windowClosesAt->greaterThan($now)) {
                continue;
            }

            $officialRole->forceFill([
                'last_window_close_date' => $windowClosesAt,
            ])->save();

            $updatedCount++;
        }

        $this->info("Updated {$updatedCount} official role window close date(s).");

        return self::SUCCESS;
    }
}
