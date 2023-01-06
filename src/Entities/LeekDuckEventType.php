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
    ) {
    }

    /**
     * The data heading into the event type seems consistent, but given its external, never leave it to chance.
     */
    protected static function sanitiseType(string $type): string
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
     * Transform the timezone identifier into something that can be used in filenames.
     */
    protected function sanitiseTimezone(string $timezone): string
    {
        return trim(
            strtolower(
                str_replace(
                    search: '/',
                    replace: '-',
                    subject: $timezone
                )
            )
        );
    }

    /**
     * Create an LeekDuckEventType object from the type name.
     */
    public static function create(string $name, string $heading): self
    {
        return new self(
            key: self::sanitiseType(
                type: $name
            ),
            name: $name,
            title: $heading
        );
    }

    /**
     * Generate the filename for the given timezone and calendar combo.
     */
    public function filename(string $timezone): string
    {
        $filename = $timezone === CalendarService::LOCAL_TIMEZONE_NAME
            ? 'gocal'
            : 'gocal__' . $this->sanitiseTimezone(timezone: $timezone);

        if ($this->name !== CalendarService::EVERYTHING_CALENDAR_NAME) {
            $filename .= "__{$this->key}";
        }

        return "{$filename}.ics";
    }

    /**
     * Generate the filepath for the given timezone and calendar combo.
     */
    public function filepath(string $timezone): string
    {
        return self::BASE_LOCAL_PATH . $this->filename(
            timezone: $timezone
        );
    }

    /**
     * Generate the download url for the given timezone and calendar combo.
     */
    public function downloadUrl(string $timezone): string
    {
        return self::BASE_DOWNLOAD_URL . $this->filename(
            timezone: $timezone
        );
    }
}
