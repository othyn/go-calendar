<?php

declare(strict_types=1);

namespace Console\Entities;

use Console\Services\CalendarService;
use Spatie\IcalendarGenerator\Components\Calendar;

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
    public static function create(LeekDuckEventType $eventType): self
    {
        return new self(
            eventType: $eventType,
            calendar: Calendar::create()
                ->name(
                    name: 'GO Calendar - ' . $eventType->title . ($eventType->title == CalendarService::EVERYTHING_CALENDAR_NAME ? '' : ' (' . acronymForEventType($eventType) . ')')
                )
                ->description(
                    description: 'All Pokémon GO ' . ($eventType->title == CalendarService::EVERYTHING_CALENDAR_NAME ? '' : "{$eventType->title} ") . 'events, in your local time, auto-updated and sourced from Leek Duck.'
                )
                ->refreshInterval(
                    minutes: 1440 // 1 day
                )
                ->withoutAutoTimezoneComponents()
                ->withoutTimezone()
        );
    }
}
