<?php

namespace App\Console\Commands;

use App\Enums\WindowPlan;
use App\Models\OfficialRole;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:force-open-windows {ids : Comma-separated OfficialRole IDs}')]
#[Description('Force open periodic windows for the provided official role IDs.')]
class ForceOpenWindows extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $ids = collect(explode(',', (string) $this->argument('ids')))
            ->map(static fn (string $id): string => trim($id))
            ->filter(static fn (string $id): bool => $id !== '')
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            $this->error('No valid IDs were provided.');

            return self::INVALID;
        }

        $openedCount = 0;
        $skippedCount = 0;
        $notFoundCount = 0;

        foreach ($ids as $id) {
            if (! ctype_digit($id)) {
                $this->warn("Skipping [{$id}] because it is not a valid numeric ID.");
                $skippedCount++;

                continue;
            }

            $officialRole = OfficialRole::query()->find((int) $id);

            if ($officialRole === null) {
                $this->warn("OfficialRole [{$id}] was not found.");
                $notFoundCount++;

                continue;
            }

            if ($officialRole->window_plan === WindowPlan::Continuously) {
                $this->info("Skipped [{$officialRole->id}] {$officialRole->name}: window is continuously open.");
                $skippedCount++;

                continue;
            }

            if ($officialRole->isWindowOpen()) {
                $this->info("Skipped [{$officialRole->id}] {$officialRole->name}: window is already open.");
                $skippedCount++;

                continue;
            }

            $monthsInterval = $officialRole->window_plan?->monthsInterval();

            if ($monthsInterval === null || $officialRole->open_window_duration === null) {
                $this->warn("Skipped [{$officialRole->id}] {$officialRole->name}: window configuration is incomplete.");
                $skippedCount++;

                continue;
            }

            $officialRole->forceFill([
                'last_window_close_date' => now()->toImmutable()->subMonthsNoOverflow($monthsInterval),
            ])->save();

            $officialRole = $officialRole->refresh();

            $windowOpensAt = $officialRole->windowOpensAt()?->toDateTimeString();
            $windowClosesAt = $officialRole->windowClosesAt()?->toDateTimeString();

            $this->info("Opened [{$officialRole->id}] {$officialRole->name}: {$windowOpensAt} -> {$windowClosesAt}");
            $openedCount++;
        }

        $this->line("Done. Opened: {$openedCount}, Skipped: {$skippedCount}, Not found: {$notFoundCount}.");

        return self::SUCCESS;
    }
}
