<?php

declare(strict_types=1);

namespace Console\Commands;

use Carbon\Carbon;
use Console\Enums\OutputGroup;
use Console\Services\OutputService;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCalendar extends Command
{
    /**
     * Shared output service.
     */
    protected OutputService $output;

    protected function configure()
    {
        $this
            ->setName(name: 'gen')
            ->setDescription(description: 'Grabs the latest data from Leek Duck (ScrapedDuck) and generates the iCal calendar')
            ->setHelp(help: 'Grabs the latest data from Leek Duck (ScrapedDuck) and generates the iCal calendar');
    }

    /**
     * The data heading into the event type seems consistent, but given its external, never leave it to chance.
     */
    protected function sanitiseEventType(string $eventType): string
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
     * Fetches the events manifest from the ScrapedDuck JSON resource.
     *
     * @throws \JsonException
     */
    protected function fetchEvents(): array
    {
        $this->output->msg(
            group: OutputGroup::SOURCE,
            message: 'Grabbing events manifest from ScrapedDuck GitHub...'
        );

        // https://github.com/bigfoott/ScrapedDuck/blob/master/docs/EVENTS.md
        $json = file_get_contents(
            filename: 'https://raw.githubusercontent.com/bigfoott/ScrapedDuck/data/events.min.json'
        );

        $this->output->msg(
            group: OutputGroup::SOURCE,
            message: 'Grabbed!'
        );

        $this->output->msg(
            group: OutputGroup::PARSE,
            message: 'Parsing events manifest...'
        );

        $events = json_decode(
            json: $json,
            associative: true,
            flags: JSON_THROW_ON_ERROR
        );

        $this->output->msg(
            group: OutputGroup::PARSE,
            message: 'Parsed!'
        );

        return $events;
    }

    /**
     * @throws \JsonException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Parent constructor doesn't get passed the shared output interface :(
        $this->output = new OutputService(output: $output);

        $this->output->msg(
            group: OutputGroup::START,
            message: 'Building latest iCal from Leek Duck events.'
        );

        $events = $this->fetchEvents();

        $this->output->msg(
            group: OutputGroup::CALENDAR,
            message: 'Creating the calendar...'
        );

//        $timezones =

        /** @var <string, array{name: string, url: string}> $calendarManifest */
        $calendarManifest = [
            'everything' => [
                'name' => 'Everything',
                'url' => 'https://github.com/othyn/go-calendar/releases/latest/download/gocal.ics',
            ],
        ];

        $everythingCalendar = Calendar::create()
            ->name(
                name: 'GO Calendar'
            )
            ->description(
                description: 'All Pokémon GO events, in your local time, auto-updated and sourced from Leek Duck.'
            )
            ->refreshInterval(
                minutes: 1440 // 1 day
            )
            ->withoutAutoTimezoneComponents()
            ->withoutTimezone();

        /** @var array<Calendar> $eventTypeCalendars */
        $eventTypeCalendars = [];

        $this->output->msg(
            group: OutputGroup::CALENDAR,
            message: 'Created!'
        );

        $this->output->msg(
            group: OutputGroup::EVENTS,
            message: 'Creating all calendar permutations from manifest...'
        );

        foreach ($events as $event) {
            $eventName = '[' . $event['heading'] . '] ' . $event['name'];

            $this->output->msg(
                group: OutputGroup::EVENT,
                message: "┌ Processing ~ {$eventName}"
            );

            $startDate = Carbon::parse(
                time: $event['start'],
                tz: new \DateTimeZone('Atlantic/Reykjavik')
            );

            $endDate = Carbon::parse(
                time: $event['end'],
                tz: new \DateTimeZone('Atlantic/Reykjavik')
            );

            $calendarEvent = Event::create()
                ->uniqueIdentifier(
                    uid: $event['eventID']
                )
                ->name(
                    name: $eventName
                )
                ->description(
                    description: 'Starts at ' . $startDate->format('H:i') . ', ends at ' . $endDate->format('H:i') . ".\n\n{$event['link']}"
                )
                ->url(
                    url: $event['link']
                )
                ->image(
                    url: $event['image']
                )
                ->alertMinutesBefore(
                    minutes: 15
                )
                ->startsAt(
                    starts: $startDate
                )
                ->endsAt(
                    ends: $endDate
                )
                ->withoutTimezone();

            $eventDurationInDays = $startDate->diffInDays($endDate);

            $isFullDay = $eventDurationInDays > 1;

            if ($isFullDay) {
                $calendarEvent->fullDay();
            }

            $everythingCalendar->event(
                event: $calendarEvent
            );

            $this->output->msg(
                group: OutputGroup::EVENT,
                message: "├ Dates ~ Starts at {$startDate->format('Y-m-d H:i')}, ends at {$endDate->format('Y-m-d H:i')}."
            );

            $this->output->msg(
                group: OutputGroup::EVENT,
                message: '├ All day? ~ ' . ($isFullDay ? 'Yes' : 'No')
            );

            $eventTypeKey = $this->sanitiseEventType($event['eventType']);

            if (! isset($eventTypeCalendars[$eventTypeKey])) {
                $eventTypeCalendars[$eventTypeKey] = Calendar::create()
                    ->name(
                        name: 'GO Calendar - ' . $event['heading']
                    )
                    ->description(
                        description: 'All Pokémon GO ' . $event['heading'] . ' events, in your local time, auto-updated and sourced from Leek Duck.'
                    )
                    ->refreshInterval(
                        minutes: 1440 // 1 day
                    )
                    ->withoutAutoTimezoneComponents()
                    ->withoutTimezone();
            }

            $eventTypeCalendars[$eventTypeKey]->event(
                event: $calendarEvent
            );

            $calendarManifest[$eventTypeKey] = [
                'name' => $event['heading'],
                'url' => "https://github.com/othyn/go-calendar/releases/latest/download/gocal__{$eventTypeKey}.ics",
            ];

            $this->output->msg(
                group: OutputGroup::EVENT,
                message: "└ Calendars ~ Added to 'All' and to '{$event['heading']}'."
            );
        }

        ksort(array: $calendarManifest);

        $this->output->msg(
            group: OutputGroup::EVENTS,
            message: 'Created!'
        );

        $this->output->msg(
            group: OutputGroup::EXPORT,
            message: 'Generating everything-calendar iCal export...'
        );

        $calendarFile = __DIR__ . '/../../dist/gocal.ics';

        file_put_contents(
            filename: $calendarFile,
            data: $everythingCalendar->get()
        );

        $this->output->msg(
            group: OutputGroup::EXPORT,
            message: "Generated to '{$calendarFile}'!"
        );

        $this->output->msg(
            group: OutputGroup::EXPORT,
            message: 'Generating all type-calendar iCal exports...'
        );

        foreach ($eventTypeCalendars as $eventTypeKey => $eventTypeCalendar) {
            $this->output->msg(
                group: OutputGroup::EXPORTSUB,
                message: "Generating {$eventTypeKey} calendar iCal export..."
            );

            $eventTypeCalendarFile = __DIR__ . "/../../dist/gocal__{$eventTypeKey}.ics";

            file_put_contents(
                filename: $eventTypeCalendarFile,
                data: $eventTypeCalendar->get()
            );

            $this->output->msg(
                group: OutputGroup::EXPORTSUB,
                message: "Generated to '{$eventTypeCalendarFile}'!"
            );
        }

        $this->output->msg(
            group: OutputGroup::EXPORT,
            message: 'Generated all type-calendars!'
        );

        $this->output->msg(
            group: OutputGroup::EXPORT,
            message: 'Generating calendar manifest...'
        );

        $calendarManifestFile = __DIR__ . '/../../dist/manifest.json';

        file_put_contents(
            filename: $calendarManifestFile,
            data: json_encode(
                value: $calendarManifest,
                flags: JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            )
        );

        $this->output->msg(
            group: OutputGroup::EXPORT,
            message: 'Created!'
        );

        $this->output->msg(
            group: OutputGroup::END,
            message: 'Calendar generate complete!'
        );

        return Command::SUCCESS;
    }
}
