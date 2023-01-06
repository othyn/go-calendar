<?php

declare(strict_types=1);

namespace Console\Entities;

use Console\Services\CalendarService;

class LeekDuckEventType
{
    /**
     * The base local file path to the generated ICS assets.
     */
    public const BASE_LOCAL_PATH = __DIR__ . '/../../dist/';

    /**
     * The base download URL to the hosted ICS assets.
     */
    public const BASE_DOWNLOAD_URL = 'https://github.com/othyn/go-calendar/releases/latest/download/';

    public function __construct(
        public string $key,
        public string $name,
        public string $title,
        public string $filename,
        public string $filepath,
        public string $url,
    ) {
    }

    /**
     * The data heading into the event type seems consistent, but given its external, never leave it to chance.
     */
    protected static function sanitise(string $type): string
    {
        return trim(
            strtolower(
                preg_replace(
                    pattern: '/[\-\ ]/i',
                    replacement: '_',
                    subject: preg_replace(
                        pattern: '/[^a-z0-9\_\-\ ]/i',
                        replacement: '',
                        subject: $type
                    )
                )
            )
        );
    }

    /**
     * Create an LeekDuckEventType object from the type name.
     */
    public static function create(string $name, string $heading): self
    {
        $key = self::sanitise(
            type: $name
        );

        $filename = $name === CalendarService::EVERYTHING_CALENDAR_NAME
            ? 'gocal.ics'
            : "gocal__{$key}.ics";

        return new self(
            key: $key,
            name: $name,
            title: $heading,
            filename: $filename,
            filepath: self::BASE_LOCAL_PATH . $filename,
            url: self::BASE_DOWNLOAD_URL . $filename
        );
    }
}
