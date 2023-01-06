<?php

declare(strict_types=1);

namespace Console\Services;

use Console\Entities\CalendarManifest;
use Console\Entities\LeekDuckEvent;
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
     * Key used by the 'one of everything' manual calendar.
     */
    public const EVERYTHING_CALENDAR_KEY = 'everything';

    /**
     * Local timezone name.
     */
    public const LOCAL_TIMEZONE_NAME = 'Local Time';

    /**
     * Timezone identifier to act as the local timezone, to get around the UTC 'bug' in the Spatie Calendar generator.
     */
    public const LOCAL_TIMEZONE_IDENTIFIER = 'Atlantic/Reykjavik';

    /**
     * Calendar manifest store, by event type.
     *
     * @var array<string, array<string, CalendarManifest>>
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

        $timezones = array_merge(
            [
                self::LOCAL_TIMEZONE_NAME,
            ],
            \DateTimeZone::listIdentifiers()
        );

        foreach ($timezones as $timezone) {
            if (! isset(self::$manifest[$timezone])) {
                self::$manifest[$timezone] = [];
            }

            foreach ($eventTypes as $eventType) {
                self::$manifest[$timezone][$eventType->key] = CalendarManifest::create(
                    eventType: $eventType,
                    timezone: $timezone
                );
            }
        }
    }

    /**
     * Adds an event to the 'one of everything' calendar and the given event types calendar, in a specific timezone.
     */
    public static function addEventToCalendar(string $timezone, LeekDuckEventType $eventType, Event $event): void
    {
        self::$manifest[$timezone][self::EVERYTHING_CALENDAR_KEY]->calendar
            ->event(
                event: $event
            );

        self::$manifest[$timezone][$eventType->key]->calendar
            ->event(
                event: $event
            );
    }

    /**
     * Add an array of LeekDuckEvent's to all timezones and calendars.
     *
     * @param array<LeekDuckEvent> $events
     */
    public static function addEventsToCalendar(array $events): void
    {
        foreach (self::$manifest as $timezone => $eventTypeCalendars) {
            foreach ($eventTypeCalendars as $manifest) {
                foreach ($events as $event) {
                    self::addEventToCalendar(
                        timezone: $timezone,
                        eventType: $manifest->eventType,
                        event: $event->asCalendarEvent(
                            timezone: $timezone
                        )
                    );
                }
            }
        }
    }

    /**
     * Export all calendars to local ICS files.
     */
    public static function exportCalendars(): void
    {
        foreach (self::$manifest as $timezone => $eventTypeCalendars) {
            foreach ($eventTypeCalendars as $manifest) {
                file_put_contents(
                    filename: $manifest->eventType->filepath(
                        timezone: $timezone
                    ),
                    data: $manifest->calendar->get()
                );
            }
        }
    }

    /**
     * Export manifest JSON.
     */
    public static function exportManifest(): void
    {
        $manifest = [];

        foreach (self::$manifest as $timezone => $eventTypeCalendars) {
            if (! isset($manifest[$timezone])) {
                $manifest[$timezone] = [];
            }

            foreach ($eventTypeCalendars as $calendarManifest) {
                $manifest[$timezone][$calendarManifest->eventType->key] = [
                    'name' => $calendarManifest->eventType->title,
                    'url' => $calendarManifest->eventType->downloadUrl(
                        timezone: $timezone
                    ),
                ];
            }
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
