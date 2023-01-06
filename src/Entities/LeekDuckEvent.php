<?php

declare(strict_types=1);

namespace Console\Entities;

use Carbon\Carbon;
use Console\Services\CalendarService;
use Spatie\IcalendarGenerator\Components\Event;

class LeekDuckEvent
{
    public function __construct(
        public string $eventId,
        public string $name,
        public string $title,
        public string $description,
        public LeekDuckEventType $type,
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
     * Create an LeekDuckEvent object from the JSON array object.
     */
    public static function create(array $event, string $timezone): self
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
            name: $event['name'],
            title: "[{$event['heading']}] {$event['name']}",
            description: 'Starts at ' . $startDate->format(format: 'H:i') . ', ends at ' . $endDate->format(format: 'H:i') . ".\n\n{$event['link']}",
            type: LeekDuckEventType::create(
                name: $event['eventType'],
                heading: $event['heading']
            ),
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
    public static function createMany(array $events, string $timezone): array
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
    public function changeTimezone(string $timezone): void
    {
        if ($timezone === CalendarService::LOCAL_TIMEZONE_NAME) {
            $timezone = CalendarService::LOCAL_TIMEZONE_IDENTIFIER;
        }

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
    public function asCalendarEvent(string $timezone): Event
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
            );

        if ($timezone === CalendarService::LOCAL_TIMEZONE_NAME) {
            $calendarEvent->withoutTimezone();
        }

        if ($this->isFullDay) {
            $calendarEvent->fullDay();
        }

        return $calendarEvent;
    }
}
