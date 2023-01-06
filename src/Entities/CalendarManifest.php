<?php

declare(strict_types=1);

namespace Console\Entities;

use Console\Services\CalendarService;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Timezone;

class CalendarManifest
{
    public function __construct(
        public LeekDuckEventType $eventType,
        public Calendar $calendar,
    ) {
    }

    /**
     * Create a new CalendarManifest object.
     */
    public static function create(LeekDuckEventType $eventType, string $timezone): self
    {
        $calendar = Calendar::create()
            ->name(
                name: 'GO Calendar - ' . $eventType->title
            )
            ->description(
                description: 'All Pokémon GO ' . ($eventType->title == CalendarService::EVERYTHING_CALENDAR_NAME ? '' : "{$eventType->title} ") . 'events, in your local time, auto-updated and sourced from Leek Duck.'
            )
            ->refreshInterval(
                minutes: 1440 // 1 day
            );

        if ($timezone === CalendarService::LOCAL_TIMEZONE_NAME) {
            $calendar
                ->withoutAutoTimezoneComponents()
                ->withoutTimezone();
        } else {
            $calendar
                ->timezone(
                    timezone: Timezone::create(
                        identifier: $timezone
                    )
                );
        }

        return new self(
            eventType: $eventType,
            calendar: $calendar
        );
    }
}
