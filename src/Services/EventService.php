<?php

declare(strict_types=1);

namespace Console\Services;

use Console\Entities\LeekDuckEvent;

class EventService
{
    /**
     * Currently taken from the docs at:
     * https://github.com/bigfoott/ScrapedDuck/blob/master/docs/EVENTS.md.
     */
    protected const EVENTS_URL = 'https://raw.githubusercontent.com/bigfoott/ScrapedDuck/data/events.min.json';

    /**
     * Fetches the events manifest from the ScrapedDuck JSON resource.
     *
     * @return array<LeekDuckEvent>
     *
     * @throws \JsonException
     */
    public static function fetch(): array
    {
        return LeekDuckEvent::createMany(
            events: json_decode(
                json: file_get_contents(
                    filename: self::EVENTS_URL
                ),
                associative: true,
                flags: JSON_THROW_ON_ERROR
            )
        );
    }
}
