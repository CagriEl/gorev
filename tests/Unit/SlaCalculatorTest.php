<?php

namespace Tests\Unit;

use App\Support\SlaCalculator;
use Carbon\Carbon;
use Tests\TestCase;

class SlaCalculatorTest extends TestCase
{
    public function test_calendar_minutes_calculation(): void
    {
        $start = Carbon::parse('2026-05-02 08:00:00');
        $end = Carbon::parse('2026-05-02 10:30:00');

        $this->assertSame(150, SlaCalculator::calendarMinutes($start, $end));
    }

    public function test_business_minutes_excludes_weekend_hours(): void
    {
        config()->set('sla.workday_start_hour', 8);
        config()->set('sla.workday_end_hour', 17);
        config()->set('sla.holidays', []);

        $start = Carbon::parse('2026-05-01 16:00:00');
        $end = Carbon::parse('2026-05-02 10:00:00'); // Saturday

        $this->assertSame(60, SlaCalculator::businessMinutes($start, $end));
    }
}
