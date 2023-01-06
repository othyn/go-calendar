<?php

declare(strict_types=1);

namespace Console\Entities;

use Carbon\Carbon;
use Spatie\IcalendarGenerator\Components\Event;

class LeekDuckEvent
{
    public function __construct(
        public string $eventId,
        public string $key,
        public string $name,
        public string $title,
        public string $description,
        public string $eventType,
        public string $heading,
        public string $link,
        public string $imageUrl,
        public string $startDateString,
        public string $endDateString,
        public Carbon $startDate,
        public Carbon $endDate,
        public int $durationInDays,
        public bool $isFullDay,
        public ?array $extraData
    ) {
    }

    /**
     * The data heading into the event type seems consistent, but given its external, never leave it to chance.
     */
    protected static function sanitiseEventType(string $eventType): string
    {
        return trim(
            strtolower(
                preg_replace(
                    pattern: '/[\-\ ]/i',
                    replacement: '_',
                    subject: preg_replace(
                        pattern: '/[^a-z0-9\_\-\ ]/i',
                        replacement: '',
                        subject: $eventType
                    )
                )
            )
        );
    }

    /**
     * Create an LeekDuckEvent object from the JSON array object.
     */
    public static function create(array $event, string $timezone = 'Atlantic/Reykjavik'): self
    {
        $startDate = Carbon::parse(
            time: $event['start'],
            tz: new \DateTimeZone(
                timezone: $timezone
            )
        );

        $endDate = Carbon::parse(
            time: $event['end'],
            tz: new \DateTimeZone(
                timezone: $timezone
            )
        );

        $eventDurationInDays = $startDate->diffInDays(
            date: $endDate
        );

        return new self(
            eventId: $event['eventID'],
            key: self::sanitiseEventType(
                eventType: $event['eventType']
            ),
            name: $event['name'],
            title: "[{$event['heading']}] {$event['name']}",
            description: 'Starts at ' . $startDate->format(format: 'H:i') . ', ends at ' . $endDate->format(format: 'H:i') . ".\n\n{$event['link']}",
            eventType: $event['eventType'],
            heading: $event['heading'],
            link: $event['link'],
            imageUrl: $event['image'],
            startDateString: $event['start'],
            endDateString: $event['end'],
            startDate: $startDate,
            endDate: $endDate,
            durationInDays: $eventDurationInDays,
            isFullDay: ($eventDurationInDays > 1),
            extraData: $event['extraData']
        );
    }

    /**
     * Create an array of LeekDuckEvent objects from the JSON array object.
     *
     * @return array<LeekDuckEvent>
     */
    public static function createMany(array $events, string $timezone = 'Atlantic/Reykjavik'): array
    {
        $parsedEvents = [];

        foreach ($events as $event) {
            $parsedEvents[] = self::create(
                event: $event,
                timezone: $timezone
            );
        }

        return $parsedEvents;
    }

    /**
     * Changes the events start and end date Carbon instances to be presented in a new timezone.
     */
    public function changeTimezone(string $timezone = 'Atlantic/Reykjavik'): void
    {
        $this->startDate = Carbon::parse(
            time: $this->startDateString,
            tz: new \DateTimeZone(
                timezone: $timezone
            )
        );

        $this->endDate = Carbon::parse(
            time: $this->endDateString,
            tz: new \DateTimeZone(
                timezone: $timezone
            )
        );
    }

    /**
     * Converts a Leek Duck event to a calendar event.
     */
    public function asCalendarEvent(string $timezone = 'Atlantic/Reykjavik'): Event
    {
        $this->changeTimezone(
            timezone: $timezone
        );

        $calendarEvent = Event::create()
            ->uniqueIdentifier(
                uid: $this->eventId
            )
            ->name(
                name: $this->title,
            )
            ->description(
                description: $this->description
            )
            ->url(
                url: $this->link
            )
            ->image(
                url: $this->imageUrl
            )
            ->alertMinutesBefore(
                minutes: 15
            )
            ->startsAt(
                starts: $this->startDate
            )
            ->endsAt(
                ends: $this->endDate
            )
            ->withoutTimezone();

        if ($this->isFullDay) {
            $calendarEvent->fullDay();
        }

        return $calendarEvent;
    }
}
