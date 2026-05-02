<?php

namespace App\Support;

use Carbon\CarbonInterface;

final class SlaCalculator
{
    public static function calendarMinutes(CarbonInterface $start, CarbonInterface $end): int
    {
        return max(0, (int) $start->diffInMinutes($end));
    }

    public static function businessMinutes(CarbonInterface $start, CarbonInterface $end): int
    {
        $workdayStart = (int) config('sla.workday_start_hour', 8);
        $workdayEnd = (int) config('sla.workday_end_hour', 17);
        $holidays = collect(config('sla.holidays', []))->values()->all();

        if ($end->lessThanOrEqualTo($start)) {
            return 0;
        }

        $cursor = $start->copy();
        $minutes = 0;
        while ($cursor->lessThan($end)) {
            $dateKey = $cursor->toDateString();
            $isWeekend = $cursor->isWeekend();
            $isHoliday = in_array($dateKey, $holidays, true);
            $isInWorkHours = $cursor->hour >= $workdayStart && $cursor->hour < $workdayEnd;
            if (! $isWeekend && ! $isHoliday && $isInWorkHours) {
                $minutes++;
            }
            $cursor->addMinute();
        }

        return $minutes;
    }
}
