<?php

declare(strict_types=1);

namespace Console\Services;

use Console\Entities\CalendarManifest;
use Console\Entities\LeekDuckEventType;
use Console\Enums\OutputGroup;
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
     * Timezone identifier to act as the local timezone, to get around the UTC 'bug' in the Spatie Calendar generator.
     *
     * Why Reykjavik?
     * It's a common trick to use it in cases like this as a substitute for UTC as it doesn't observe daylight savings.
     */
    public const TIMEZONE = 'Atlantic/Reykjavik';

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
        self::$manifest[self::EVERYTHING_CALENDAR_KEY]->calendar
            ->event(
                event: $event
            );

        self::$manifest[$eventType->key]->calendar
            ->event(
                event: $event
            );
    }

    /**
     * Add an array of LeekDuckEvent's to all calendars.
     *
     * @param array<LeekDuckEvent> $events
     */
    public static function addEventsToCalendar(array $events, OutputService $output): void
    {
        foreach ($events as $event) {
            $output->msg(
                group: OutputGroup::EVENT,
                message: '┬───────────┬─────────┄'
            );

            $output->msg(
                group: OutputGroup::EVENT,
                message: "┤     Event │ {$event->title}"
            );

            CalendarService::addEventToCalendar(
                eventType: $event->type,
                event: $event->asCalendarEvent()
            );

            $output->msg(
                group: OutputGroup::EVENT,
                message: '┼───────────┼─────────┄'
            );

            $output->msg(
                group: OutputGroup::EVENT,
                message: "┤    Period │ Starts at {$event->startDate->format(format: 'Y-m-d H:i')}, ends at {$event->endDate->format(format: 'Y-m-d H:i')}."
            );

            $output->msg(
                group: OutputGroup::EVENT,
                message: '┼───────────┼─────────┄'
            );

            $output->msg(
                group: OutputGroup::EVENT,
                message: '┤  All day? │ ' . ($event->isFullDay ? 'Yes' : 'No')
            );

            $output->msg(
                group: OutputGroup::EVENT,
                message: '┼───────────┼─────────┄'
            );

            $output->msg(
                group: OutputGroup::EVENT,
                message: "┤ Calendars │ Added to '" . CalendarService::EVERYTHING_CALENDAR_NAME . "' and to '{$event->type->title}'."
            );

            $output->msg(
                group: OutputGroup::EVENT,
                message: '┴───────────┴─────────┄',
                addExtraNewline: true
            );
        }
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
                'name' => $calendarManifest->eventType->title,
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
