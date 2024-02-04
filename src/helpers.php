<?php

declare(strict_types=1);

use Console\Entities\LeekDuckEventType;

if (! function_exists(function: 'dd')) {
    function dd(mixed $var): void
    {
        var_dump($var);
        exit;
    }
}

// https://laravel-news.com/laravel-str-acronym
if (! function_exists(function: 'acronym')) {
    function acronym(string $string, $delimiter = ''): string
    {
        if (empty($string)) {
            return '';
        }

        $acronym = '';

        foreach (preg_split('/[^\p{L}]+/u', $string) as $word) {
            if (!empty($word)) {
                $first_letter = mb_substr($word, 0, 1);
                $acronym .= $first_letter . $delimiter;
            }
        }

        return $acronym;
    }
}

if (! function_exists(function: 'acronymForEventType')) {
    function acronymForEventType(LeekDuckEventType $eventType): string
    {
        // Otherwise it clashes with Raid Battles (RB)
        return acronym(
            string: $eventType->title == 'Research Breakthrough'
                ? 'Research Break Through'
                : $eventType->title,
            delimiter: ''
        );
    }
}
