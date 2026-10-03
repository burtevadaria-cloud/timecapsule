<?php

declare(strict_types=1);

namespace App\Support;

// Small text-formatting helpers used by the views. All dates are UTC.
class Format
{
    // "2027-01-01 10:00:00" -> "1 Jan 2027, 10:00 UTC"
    public static function dateTime(string $timestamp): string
    {
        return gmdate('j M Y, H:i', strtotime($timestamp)) . ' UTC';
    }

    // "2027-01-01 10:00:00" -> "2027-01-01T10:00:00Z" (for <time datetime="...">)
    public static function iso(string $timestamp): string
    {
        return gmdate('Y-m-d\TH:i:s\Z', strtotime($timestamp));
    }

    // Text shown for a capsule that is not opened yet. $now is only passed by tests.
    public static function countdown(string $openAt, ?int $now = null): string
    {
        $seconds = strtotime($openAt) - ($now ?? time());
        if ($seconds <= 0) {
            // The time has passed; the next request opens the capsule.
            return 'Opening soon…';
        }

        if ($seconds < 3600) {
            [$count, $unit] = [(int) ceil($seconds / 60), 'minute'];
        } elseif ($seconds < 86400) {
            [$count, $unit] = [intdiv($seconds, 3600), 'hour'];
        } else {
            [$count, $unit] = [intdiv($seconds, 86400), 'day'];
        }
        return "Opens in $count $unit" . ($count === 1 ? '' : 's');
    }

    // First 120 characters of a message. mb_* functions count characters, not bytes.
    public static function excerpt(string $message, int $length = 120): string
    {
        return mb_strlen($message) > $length ? mb_substr($message, 0, $length) . '…' : $message;
    }

    // "anna@example.com" -> "anna"
    public static function authorName(string $email): string
    {
        return explode('@', $email, 2)[0];
    }
}
