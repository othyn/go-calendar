<?php

declare(strict_types=1);

namespace Console\Services;

use Console\Entities\CalendarManifest;
use Console\Entities\LeekDuckEventType;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;

class CalendarService
{
    /**
     * Name used by the 'one of everything' manual calendar.
     */
    public const EVERYTHING_CALENDAR_NAME = 'Everything';

    /**
     * Calendar manifest store, by event type.
     *
     * @var array<LeekDuckEventType, CalendarManifest>
     */
    protected static array $manifest;

    /**
     * Create Calendars from the given event types.
     *
     * @param array<LeekDuckEventType> $eventTypes
     */
    public static function createCalendars(array $eventTypes): void
    {
        if (! empty(self::$manifest)) {
            return;
        }

        foreach ($eventTypes as $eventType) {
            self::$manifest[$eventType->key] = CalendarManifest::create(
                eventType: $eventType
            );
        }
    }

    /**
     * Adds an event to the 'one of everything' calendar and the given event types calendar.
     */
    public static function addEventToCalendar(LeekDuckEventType $eventType, Event $event): void
    {
        self::$manifest[self::EVERYTHING_CALENDAR_NAME]->calendar
            ->event(
                event: $event
            );

        self::$manifest[$eventType->key]->calendar
            ->event(
                event: $event
            );
    }

    /**
     * Export all calendars to local ICS files.
     */
    public static function exportCalendars(): void
    {
        foreach (self::$manifest as $manifest) {
            file_put_contents(
                filename: $manifest->eventType->filepath,
                data: $manifest->calendar->get()
            );
        }
    }

    /**
     * Export manifest JSON.
     */
    public static function exportManifest(): void
    {
        $manifest = [];

        foreach (self::$manifest as $calendarManifest) {
            $manifest[$calendarManifest->eventType->key] = [
                'name' => $calendarManifest->eventType->name,
                'url' => $calendarManifest->eventType->url,
            ];
        }

        file_put_contents(
            filename: LeekDuckEventType::BASE_LOCAL_PATH . 'manifest.json',
            data: json_encode(
                value: $manifest,
                flags: JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            )
        );
    }
}
