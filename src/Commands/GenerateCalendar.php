<?php

declare(strict_types=1);

namespace Console\Commands;

use Console\Enums\OutputGroup;
use Console\Services\CalendarService;
use Console\Services\EventService;
use Console\Services\OutputService;
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
            message: 'Generating calendars from Leek Duck events.'
        );

        $this->output->msg(
            group: OutputGroup::SOURCE,
            message: 'Grabbing events from ScrapedDuck GitHub...'
        );

        $events = EventService::fetchEvents();

        $this->output->msg(
            group: OutputGroup::SOURCE,
            message: 'Events grabbed!'
        );

        $this->output->msg(
            group: OutputGroup::CALENDAR,
            message: 'Creating calendars...'
        );

        CalendarService::createCalendars(
            eventTypes: EventService::fetchEventTypes()
        );

        $this->output->msg(
            group: OutputGroup::CALENDAR,
            message: 'Calendars created!'
        );

        $this->output->msg(
            group: OutputGroup::EVENTS,
            message: 'Adding all events to calendars...'
        );

        CalendarService::addEventsToCalendar(
            events: $events,
            output: $this->output
        );

        $this->output->msg(
            group: OutputGroup::EVENTS,
            message: 'All events added to calendars!'
        );

        $this->output->msg(
            group: OutputGroup::EXPORT,
            message: 'Exporting all calendars...'
        );

        CalendarService::exportCalendars();

        $this->output->msg(
            group: OutputGroup::EXPORT,
            message: 'Exported all calendars!'
        );

        $this->output->msg(
            group: OutputGroup::EXPORT,
            message: 'Exporting calendar manifest...'
        );

        CalendarService::exportManifest();

        $this->output->msg(
            group: OutputGroup::EXPORT,
            message: 'Exported calendar manifest!'
        );

        $this->output->msg(
            group: OutputGroup::END,
            message: 'Calendar generation complete!'
        );

        return Command::SUCCESS;
    }
}
