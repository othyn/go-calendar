<?php

declare(strict_types=1);

namespace Console\Commands;

use Carbon\Carbon;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCalendar extends Command
{
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
     * @throws \JsonException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(
            messages: [
                '┌<[ Building latest iCal from Leek Duck events ]>',
                '├─[SOURCE] Grabbing events manifest from ScrapedDuck GitHub...',
            ]
        );

        // https://github.com/bigfoott/ScrapedDuck/blob/master/docs/EVENTS.md
        $json = file_get_contents(
            filename: 'https://raw.githubusercontent.com/bigfoott/ScrapedDuck/data/events.min.json'
        );

        $output->writeln(
            messages: [
                '├─[SOURCE] Grabbed!',
                '├─[PARSE] Parsing events manifest...',
            ]
        );

        $events = json_decode(
            json: $json,
            associative: true,
            flags: JSON_THROW_ON_ERROR
        );

        $output->writeln(
            messages: [
                '├─[PARSE] Parsed!',
                '├─[CAL] Creating the calendar...',
            ]
        );

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

        $output->writeln(
            messages: [
                '├─[CAL] Created!',
                '├─[EVENTS] Creating all calendar permutations from manifest...',
            ]
        );

        foreach ($events as $event) {
            $eventName = '[' . $event['heading'] . '] ' . $event['name'];

            $output->writeln(
                messages: "├──[EVENT] ┌ Processing ~ {$eventName}"
            );

            $startDate = Carbon::parse(
                time: $event['start'],
                tz: new \DateTimeZone('Europe/London')
            );

            $endDate = Carbon::parse(
                time: $event['end'],
                tz: new \DateTimeZone('Europe/London')
            );

            $calendarEvent = Event::create()
                ->name(
                    name: $eventName
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
                $calendarEvent
                    ->fullDay()
                    ->description(
                        description: 'Starts at ' . $startDate->format('H:i') . ', ends at ' . $endDate->format('H:i') . '.'
                    );
            }

            $everythingCalendar->event(
                event: $calendarEvent
            );

            $output->writeln(
                messages: [
                    "├──[EVENT] ├ Dates ~ Starts at {$startDate->format('Y-m-d H:i')}, ends at {$endDate->format('Y-m-d H:i')}.",
                    '├──[EVENT] ├ All day? ~ ' . ($isFullDay ? 'Yes' : 'No'),
                ]
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

            $output->writeln(
                messages: "├──[EVENT] └ Calendars ~ Added to 'All' and to '{$event['heading']}'."
            );
        }

        ksort(array: $calendarManifest);

        $output->writeln(
            messages: [
                '├─[EVENTS] Created!',
                '├─[EXPORT-EVERYTHING] Generating everything-calendar iCal export...',
            ]
        );

        $calendarFile = __DIR__ . '/../../dist/gocal.ics';

        file_put_contents(
            filename: $calendarFile,
            data: $everythingCalendar->get()
        );

        $output->writeln(
            messages: [
                "├─[EXPORT-EVERYTHING] Generated to '{$calendarFile}'!",
                '├─[EXPORT-TYPES] Generating all type-calendar iCal exports...',
            ]
        );

        foreach ($eventTypeCalendars as $eventTypeKey => $eventTypeCalendar) {
            $output->writeln(
                messages: [
                    "├──[EXPORT-TYPE] Generating {$eventTypeKey} calendar iCal export...",
                ]
            );

            $eventTypeCalendarFile = __DIR__ . "/../../dist/gocal__{$eventTypeKey}.ics";

            file_put_contents(
                filename: $eventTypeCalendarFile,
                data: $eventTypeCalendar->get()
            );

            $output->writeln(
                messages: [
                    "├──[EXPORT-TYPE] Generated to '{$eventTypeCalendarFile}'!",
                ]
            );
        }

        $output->writeln(
            messages: [
                '├─[EXPORT-TYPES] Generated all type-calendars!',
                '├─[EXPORT-MANIFEST] Generating calendar manifest...',
            ]
        );

        $calendarManifestFile = __DIR__ . '/../../dist/manifest.json';

        file_put_contents(
            filename: $calendarManifestFile,
            data: json_encode(
                value: $calendarManifest,
                flags: JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            )
        );

        $output->writeln(
            messages: [
                '├─[EVENTS-MANIFEST] Created!',
                '└<[ Calendar generate complete! ]>',
            ]
        );

        return Command::SUCCESS;
    }
}
