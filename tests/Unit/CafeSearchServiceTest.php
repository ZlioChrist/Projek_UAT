<?php

use App\Services\CafeSearchService;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->service = new CafeSearchService;
});

// --- isTimeInRange ---

it('returns true when current time is within normal range', function () {
    expect($this->service->isTimeInRange('08:00', '22:00', '10:00:00'))->toBeTrue();
    expect($this->service->isTimeInRange('08:00', '22:00', '21:59:00'))->toBeTrue();
});

it('returns false when current time is outside normal range', function () {
    expect($this->service->isTimeInRange('08:00', '22:00', '23:00:00'))->toBeFalse();
    expect($this->service->isTimeInRange('08:00', '22:00', '07:59:00'))->toBeFalse();
});

it('returns true at exact open time', function () {
    expect($this->service->isTimeInRange('08:00', '22:00', '08:00:00'))->toBeTrue();
});

it('returns true at exact close time', function () {
    expect($this->service->isTimeInRange('08:00', '22:00', '22:00:00'))->toBeTrue();
});

it('handles overnight range correctly — inside range', function () {
    // Open 22:00, Close 02:00 → 01:00 should be open
    expect($this->service->isTimeInRange('22:00', '02:00', '01:00:00'))->toBeTrue();
    // 23:00 should also be open
    expect($this->service->isTimeInRange('22:00', '02:00', '23:00:00'))->toBeTrue();
});

it('handles overnight range correctly — outside range', function () {
    // Open 22:00, Close 02:00 → 03:00 should be closed
    expect($this->service->isTimeInRange('22:00', '02:00', '03:00:00'))->toBeFalse();
    // 10:00 should also be closed
    expect($this->service->isTimeInRange('22:00', '02:00', '10:00:00'))->toBeFalse();
});

it('normalizes H:i format to H:i:s', function () {
    // Should work with both 5-char and 8-char formats
    expect($this->service->isTimeInRange('08:00', '22:00', '10:00:00'))->toBeTrue();
});

it('handles midnight edge case', function () {
    // Midnight is 00:00:00
    expect($this->service->isTimeInRange('22:00', '02:00', '00:00:00'))->toBeTrue();
});

// --- isWeekend ---

it('correctly identifies weekdays', function () {
    Carbon::setTestNow(Carbon::parse('next monday'));
    expect($this->service->isWeekend())->toBeFalse();

    Carbon::setTestNow(Carbon::parse('next wednesday'));
    expect($this->service->isWeekend())->toBeFalse();

    Carbon::setTestNow(Carbon::parse('next friday'));
    expect($this->service->isWeekend())->toBeFalse();
});

it('correctly identifies weekends', function () {
    Carbon::setTestNow(Carbon::parse('next saturday'));
    expect($this->service->isWeekend())->toBeTrue();

    Carbon::setTestNow(Carbon::parse('next sunday'));
    expect($this->service->isWeekend())->toBeTrue();
});
