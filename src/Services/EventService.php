<?php

declare(strict_types=1);

namespace Console\Services;

use Console\Entities\LeekDuckEvent;
use Console\Entities\LeekDuckEventType;

class EventService
{
    /**
     * Currently taken from the docs at:
     * https://github.com/bigfoott/ScrapedDuck/blob/master/docs/EVENTS.md.
     */
    protected const EVENTS_URL = 'https://raw.githubusercontent.com/bigfoott/ScrapedDuck/data/events.min.json';

    /**
     * Cache the parsed JSON response.
     */
    protected static array $jsonCache;

    /**
     * Cache the parsed Events.
     *
     * @var array<LeekDuckEvent>
     */
    protected static array $eventCache;

    /**
     * Cache the parsed Event types.
     *
     * @var array<LeekDuckEventType>
     */
    protected static array $eventTypeCache;

    /**
     * Fetches and parses the events manifest from the ScrapedDuck JSON resource.
     *
     * @throws \JsonException
     */
    protected static function fetch(): array
    {
        if (empty(self::$jsonCache)) {
            self::$jsonCache = json_decode(
                json: file_get_contents(
                    filename: self::EVENTS_URL
                ),
                associative: true,
                flags: JSON_THROW_ON_ERROR
            );
        }

        return self::$jsonCache;
    }

    /**
     * Fetches events from the ScrapedDuck resource.
     *
     * @return array<LeekDuckEvent>
     *
     * @throws \JsonException
     */
    public static function fetchEvents(string $timezone = CalendarService::LOCAL_TIMEZONE_IDENTIFIER): array
    {
        if (empty(self::$eventCache)) {
            self::$eventCache = LeekDuckEvent::createMany(
                events: self::fetch(),
                timezone: $timezone
            );
        }

        return self::$eventCache;
    }

    /**
     * Fetches event types from the ScrapedDuck resource.
     *
     * @return array<LeekDuckEventType>
     *
     * @throws \JsonException
     */
    public static function fetchEventTypes(): array
    {
        if (empty(self::$eventTypeCache)) {
            $types = [
                LeekDuckEventType::create(
                    name: CalendarService::EVERYTHING_CALENDAR_NAME,
                    heading: CalendarService::EVERYTHING_CALENDAR_NAME
                ),
            ];

            foreach (self::fetchEvents() as $event) {
                if (! in_array(needle: $event->type, haystack: $types)) {
                    $types[] = $event->type;
                }
            }

            sort(array: $types);

            self::$eventTypeCache = $types;
        }

        return self::$eventTypeCache;
    }
}
