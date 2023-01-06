<?php

declare(strict_types=1);

namespace Console\Commands;

use Console\Enums\OutputGroup;
use Console\Services\EventService;
use Console\Services\OutputService;
use Spatie\IcalendarGenerator\Components\Calendar;
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

        $this->output->msg(
            group: OutputGroup::SOURCE,
            message: 'Grabbing events manifest from ScrapedDuck GitHub...'
        );

        $events = EventService::fetch();

        $this->output->msg(
            group: OutputGroup::SOURCE,
            message: 'Grabbed!'
        );

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
            $this->output->msg(
                group: OutputGroup::EVENT,
                message: "┌ Processing ~ {$event->title}"
            );

            if (! isset($eventTypeCalendars[$event->key])) {
                $eventTypeCalendars[$event->key] = Calendar::create()
                    ->name(
                        name: 'GO Calendar - ' . $event->heading
                    )
                    ->description(
                        description: 'All Pokémon GO ' . $event->heading . ' events, in your local time, auto-updated and sourced from Leek Duck.'
                    )
                    ->refreshInterval(
                        minutes: 1440 // 1 day
                    )
                    ->withoutAutoTimezoneComponents()
                    ->withoutTimezone();
            }

            $calendarEvent = $event->asCalendarEvent();

            $everythingCalendar->event(
                event: $calendarEvent
            );

            $eventTypeCalendars[$event->key]->event(
                event: $calendarEvent
            );

            $calendarManifest[$event->key] = [
                'name' => $event->heading,
                'url' => "https://github.com/othyn/go-calendar/releases/latest/download/gocal__{$event->key}.ics",
            ];

            $this->output->msg(
                group: OutputGroup::EVENT,
                message: "├ Dates ~ Starts at {$event->startDate->format(format: 'Y-m-d H:i')}, ends at {$event->endDate->format(format: 'Y-m-d H:i')}."
            );

            $this->output->msg(
                group: OutputGroup::EVENT,
                message: '├ All day? ~ ' . ($event->isFullDay ? 'Yes' : 'No')
            );

            $this->output->msg(
                group: OutputGroup::EVENT,
                message: "└ Calendars ~ Added to 'All' and to '{$event->heading}'."
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

        foreach ($eventTypeCalendars as $eventKey => $eventTypeCalendar) {
            $this->output->msg(
                group: OutputGroup::EXPORTSUB,
                message: "Generating {$eventKey} calendar iCal export..."
            );

            $eventTypeCalendarFile = __DIR__ . "/../../dist/gocal__{$eventKey}.ics";

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
