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
            ->setDescription(description: 'Grabs the latest data from Leek Duck (ScrapedDuck) and generates the iCal calendar.')
            ->setHelp(help: 'Grabs the latest data from Leek Duck (ScrapedDuck) and generates the iCal calendar.');
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

        $calendar = Calendar::create()
            ->name(
                name: 'Pokémon GO - Unofficial Event Calendar'
            )
            ->description(
                description: 'Lists all events, in your local time, sourced from Leek Duck.'
            )
            ->refreshInterval(
                minutes: 1440 // 1 day
            )
            ->withoutTimezone();

        $output->writeln(
            messages: [
                '├─[CAL] Created!',
                '├─[EVENTS] Creating calendar events from manifest...',
            ]
        );

        foreach ($events as $event) {
            $eventName = '[' . $event['heading'] . '] ' . $event['name'];

            $output->writeln(
                messages: "├──[EVENT] Processing ~ {$eventName}"
            );

            $startDate = Carbon::parse(
                time: $event['start']
            );

            $endDate = Carbon::parse(
                time: $event['end']
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

            if ($eventDurationInDays > 1) {
                $calendarEvent
                    ->fullDay()
                    ->description(
                        description: 'Starts at ' . $startDate->format('H:i') . ', ends at ' . $endDate->format('H:i') . '.'
                    );
            }

            $calendar->event(
                event: $calendarEvent
            );
        }

        $output->writeln(
            messages: [
                '├─[EVENTS] Created!',
                '├─[EXPORT] Generating iCal export...',
            ]
        );

        $calendarFile = __DIR__ . '/../../dist/gocal.ics';

        file_put_contents(
            filename: $calendarFile,
            data: $calendar->get()
        );

        $output->writeln(
            messages: [
                "├─[EXPORT] Generated to '{$calendarFile}'!",
                '└<[ Calendar generate complete! ]>',
            ]
        );

        return Command::SUCCESS;
    }
}
