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
        public float $durationInDays,
        public bool $isFullDay,
        public ?array $extraData,
    ) {
    }

    /**
     * Create an LeekDuckEvent object from the JSON array object.
     */
    public static function create(array $event, string $timezone = CalendarService::TIMEZONE): ?self
    {
        // Skip events with null start or end dates
        if (empty($event['start']) || empty($event['end'])) {
            return null;
        }

        $startDate = Carbon::parse(
            time: $event['start'],
            timezone: new \DateTimeZone(
                timezone: $timezone
            )
        );

        $endDate = Carbon::parse(
            time: $event['end'],
            timezone: new \DateTimeZone(
                timezone: $timezone
            )
        );

        $eventDurationInDays = $startDate->diffInDays(
            date: $endDate
        );

        $eventType = LeekDuckEventType::create(
            name: $event['eventType'],
            heading: $event['heading']
        );

        return new self(
            eventId: $event['eventID'],
            name: $event['name'],
            title: '[' . acronymForEventType($eventType) . "] {$event['name']}",
            description: 'Starts at ' . $startDate->format(format: 'H:i') . ', ends at ' . $endDate->format(format: 'H:i') . ".\n\n{$event['link']}",
            type: $eventType,
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
    public static function createMany(array $events, string $timezone = CalendarService::TIMEZONE): array
    {
        $parsedEvents = [];

        foreach ($events as $event) {
            $createdEvent = self::create(
                event: $event,
                timezone: $timezone
            );

            if ($createdEvent !== null) {
                $parsedEvents[] = $createdEvent;
            }
        }

        return $parsedEvents;
    }

    /**
     * Changes the events start and end date Carbon instances to be presented in a new timezone.
     */
    public function changeTimezone(string $timezone = CalendarService::TIMEZONE): void
    {
        $this->startDate = Carbon::parse(
            time: $this->startDateString,
            timezone: new \DateTimeZone(
                timezone: $timezone
            )
        );

        $this->endDate = Carbon::parse(
            time: $this->endDateString,
            timezone: new \DateTimeZone(
                timezone: $timezone
            )
        );
    }

    /**
     * Converts a Leek Duck event to a calendar event.
     */
    public function asCalendarEvent(string $timezone = CalendarService::TIMEZONE): Event
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
                minutes: 15,
                message: $this->title
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
