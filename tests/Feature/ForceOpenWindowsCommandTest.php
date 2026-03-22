<?php

use App\Enums\WindowDuration;
use App\Enums\WindowPlan;
use App\Models\OfficialRole;

test('command opens closed periodic windows for provided ids', function () {
    $closedPeriodicRole = OfficialRole::factory()->create([
        'window_plan' => WindowPlan::Every2Months,
        'open_window_duration' => WindowDuration::SevenDays,
        'last_window_close_date' => now()->subMonthsNoOverflow(3),
    ]);
    $closeDateBefore = $closedPeriodicRole->last_window_close_date?->copy();

    $this->artisan("app:force-open-windows {$closedPeriodicRole->id}")
        ->expectsOutputToContain("Opened [{$closedPeriodicRole->id}]")
        ->assertSuccessful();

    $closedPeriodicRole->refresh();

    expect($closedPeriodicRole->isWindowOpen())->toBeTrue()
        ->and($closedPeriodicRole->last_window_close_date?->equalTo($closeDateBefore))->toBeFalse();
});

test('command skips continuously open, already open, and not found ids', function () {
    $closedPeriodicRole = OfficialRole::factory()->create([
        'window_plan' => WindowPlan::Every3Months,
        'open_window_duration' => WindowDuration::SevenDays,
        'last_window_close_date' => now()->subMonthsNoOverflow(4),
    ]);
    $continuouslyOpenRole = OfficialRole::factory()->create([
        'window_plan' => WindowPlan::Continuously,
        'open_window_duration' => WindowDuration::SevenDays,
        'last_window_close_date' => now()->subDays(40),
    ]);
    $alreadyOpenPeriodicRole = OfficialRole::factory()->create([
        'window_plan' => WindowPlan::Every2Months,
        'open_window_duration' => WindowDuration::SevenDays,
        'last_window_close_date' => now()->subMonthsNoOverflow(2)->subDays(3),
    ]);

    $continuousCloseDateBefore = $continuouslyOpenRole->last_window_close_date?->copy();
    $alreadyOpenCloseDateBefore = $alreadyOpenPeriodicRole->last_window_close_date?->copy();

    $this->artisan("app:force-open-windows {$closedPeriodicRole->id},{$continuouslyOpenRole->id},{$alreadyOpenPeriodicRole->id},999999")
        ->expectsOutputToContain("Opened [{$closedPeriodicRole->id}]")
        ->expectsOutputToContain("Skipped [{$continuouslyOpenRole->id}]")
        ->expectsOutputToContain("Skipped [{$alreadyOpenPeriodicRole->id}]")
        ->expectsOutputToContain('OfficialRole [999999] was not found.')
        ->assertSuccessful();

    $closedPeriodicRole->refresh();
    $continuouslyOpenRole->refresh();
    $alreadyOpenPeriodicRole->refresh();

    expect($closedPeriodicRole->isWindowOpen())->toBeTrue()
        ->and($continuouslyOpenRole->last_window_close_date?->equalTo($continuousCloseDateBefore))->toBeTrue()
        ->and($alreadyOpenPeriodicRole->last_window_close_date?->equalTo($alreadyOpenCloseDateBefore))->toBeTrue();
});
