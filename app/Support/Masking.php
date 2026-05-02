<?php

namespace App\Support;

final class Masking
{
    public static function email(?string $email): string
    {
        if (! $email) {
            return '—';
        }

        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        if ($domain === '') {
            return '***';
        }
        $visible = substr($local, 0, 2);

        return $visible.str_repeat('*', max(strlen($local) - 2, 2)).'@'.$domain;
    }

    public static function phone(?string $phone): string
    {
        if (! $phone) {
            return '—';
        }
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (strlen($digits) < 4) {
            return '***';
        }

        return str_repeat('*', max(0, strlen($digits) - 4)).substr($digits, -4);
    }
}
