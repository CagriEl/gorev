<?php

return [
    'default_mode' => env('SLA_MODE', 'calendar'),
    'workday_start_hour' => (int) env('SLA_WORKDAY_START_HOUR', 8),
    'workday_end_hour' => (int) env('SLA_WORKDAY_END_HOUR', 17),
    'holidays' => array_filter(array_map('trim', explode(',', (string) env('SLA_HOLIDAYS', '')))),
];
