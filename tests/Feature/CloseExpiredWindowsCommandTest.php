<?php

use App\Enums\WindowDuration;
use App\Enums\WindowPlan;
use App\Models\OfficialRole;

test('command updates last window close date for expired periodic roles', function () {
    $expiredRole = OfficialRole::factory()->create([
        'window_plan' => WindowPlan::Every2Months,
        'open_window_duration' => WindowDuration::SevenDays,
        'last_window_close_date' => now()->subMonthsNoOverflow(2)->subDays(10),
    ]);
    $expectedCloseDate = $expiredRole->windowClosesAt();

    $this->artisan('app:close-expired-windows')
        ->assertSuccessful();

    expect($expectedCloseDate)->not->toBeNull();

    $expiredRole->refresh();

    expect($expiredRole->last_window_close_date?->equalTo($expectedCloseDate))->toBeTrue();
});

test('command does not update non expired and continuously open roles', function () {
    $notExpiredPeriodicRole = OfficialRole::factory()->create([
        'window_plan' => WindowPlan::Every2Months,
        'open_window_duration' => WindowDuration::SevenDays,
        'last_window_close_date' => now()->subMonthsNoOverflow(2)->subDays(3),
    ]);
    $continuousRole = OfficialRole::factory()->create([
        'window_plan' => WindowPlan::Continuously,
        'open_window_duration' => WindowDuration::SevenDays,
        'last_window_close_date' => now()->subDays(30),
    ]);
    $periodicCloseDateBefore = $notExpiredPeriodicRole->last_window_close_date?->copy();
    $continuousCloseDateBefore = $continuousRole->last_window_close_date?->copy();

    $this->artisan('app:close-expired-windows')
        ->assertSuccessful();

    $notExpiredPeriodicRole->refresh();
    $continuousRole->refresh();

    expect($notExpiredPeriodicRole->last_window_close_date?->equalTo($periodicCloseDateBefore))->toBeTrue()
        ->and($continuousRole->last_window_close_date?->equalTo($continuousCloseDateBefore))->toBeTrue();
});
