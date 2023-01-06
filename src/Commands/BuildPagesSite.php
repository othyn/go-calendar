<?php

declare(strict_types=1);

namespace Console\Commands;

use Console\Entities\View;
use Console\Enums\OutputGroup;
use Console\Services\OutputService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BuildPagesSite extends Command
{
    protected const ROOT_DIRECTORY = __DIR__ . '/../..';
    protected const DIST_DIRECTORY = self::ROOT_DIRECTORY . '/dist';
    protected const PAGES_DIRECTORY = self::ROOT_DIRECTORY . '/pages';
    protected const PAGES_DIST_DIRECTORY = self::PAGES_DIRECTORY . '/dist';
    protected const PAGES_TEMPLATES_DIRECTORY = self::PAGES_DIRECTORY . '/templates';

    /**
     * All views that all other templates resolve down into and are expected as compiled output views.
     */
    protected const RESOLVE_DOWN_TO_TEMPLATES = [
        'index',
    ];

    /**
     * Shared output service.
     */
    protected OutputService $output;

    protected function configure()
    {
        $this
            ->setName(name: 'site')
            ->setDescription(description: 'Builds the view templates into a static site')
            ->setHelp(help: 'Builds the view templates into a static site');
    }

    /**
     * @throws \JsonException
     */
    protected function getCalendarManifest(): array
    {
        return json_decode(
            json: file_get_contents(
                filename: self::DIST_DIRECTORY . '/manifest.json'
            ),
            associative: true,
            flags: JSON_THROW_ON_ERROR
        );
    }

    /**
     * Resolves all view templates into a compiled string, this is one dimensional in its current iteration, meaning
     *  the expectation is that all templates will resolve into a single index file. However, it wouldn't be difficult
     *  to refactor if more functionality than this is required in the future.
     *
     * @return array<string, View>
     */
    protected function render(array $with): array
    {
        $views = $this->loadViews();

        $views = $this->resolveLoopVariables(
            views: $views,
            with: $with
        );

        $views = $this->resolveVariables(
            views: $views,
            with: $with
        );

        $views = $this->resolveIncludes(
            views: $views
        );

        return array_filter(
            array: $views,
            callback: fn ($view) => $view->shouldExport
        );
    }

    /**
     * Load all views from the expected templates' directory.
     *
     * @return array<string, View>
     */
    protected function loadViews(): array
    {
        $iterator = new \RecursiveIteratorIterator(
            iterator: new \RecursiveDirectoryIterator(
                directory: self::PAGES_TEMPLATES_DIRECTORY,
                flags: \FilesystemIterator::SKIP_DOTS
            )
        );

        $views = [];

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            $contents = file_get_contents(
                filename: $file->getRealPath()
            );

            $filenameWithoutExtension = pathinfo(
                path: $file->getFilename(),
                flags: PATHINFO_FILENAME
            );

            // Form a known array structure keyed by the file name, so we can pull a view by filename later for includes
            $views[$filenameWithoutExtension] = new View(
                name: $filenameWithoutExtension,
                file: $file,
                contents: $contents,
                shouldExport: in_array(
                    needle: $filenameWithoutExtension,
                    haystack: self::RESOLVE_DOWN_TO_TEMPLATES
                )
            );
        }

        return $views;
    }

    /**
     * Iterate over all passed views and resolve any includes to other templates.
     *
     * @param array<string, View> $views
     *
     * @return array<string, View>
     */
    protected function resolveIncludes(array $views): array
    {
        foreach ($views as &$view) {
            preg_match_all(
                pattern: "/\@include\{(.*?)}/",
                subject: $view->contents,
                matches: $includeMatches
            );

            foreach ($includeMatches[1] as $filenameWithoutExtension) {
                $view->contents = trim(
                    string: str_replace(
                        search: "@include{{$filenameWithoutExtension}}",
                        replace: $views[$filenameWithoutExtension]->contents,
                        subject: $view->contents
                    )
                );
            }
        }

        return $views;
    }

    /**
     * Iterate over all passed views and resolve any loop variables.
     *
     * @param array<string, View> $views
     *
     * @return array<string, View>
     */
    protected function resolveLoopVariables(array $views, array $with): array
    {
        foreach ($views as &$view) {
            preg_match_all(
                pattern: "/\@loop\{(.*?)\}(.*?)\@endloop/s",
                subject: $view->contents,
                matches: $loopMatches
            );

            for (
                $loopMatchSet = 0;
                $loopMatchSet < count($loopMatches[0]);
                ++$loopMatchSet
            ) {
                $loopRaw = $loopMatches[0][$loopMatchSet];
                $loopName = $loopMatches[1][$loopMatchSet];
                $loopContent = $loopMatches[2][$loopMatchSet];

                $iterationContent = '';

                // Parse variables for each source loop element
                foreach ($with[$loopName] as $value) {
                    $iterationContent .= $this->substituteVariables(
                        in: $loopContent,
                        with: $value
                    );
                }

                // Replace the loop markup with compiled content
                $view->contents = trim(
                    string: str_replace(
                        search: $loopRaw,
                        replace: $iterationContent,
                        subject: $view->contents
                    )
                );
            }
        }

        return $views;
    }

    /**
     * Iterate over all passed views and resolve any variables.
     *
     * @param array<string, View> $views
     *
     * @return array<string, View>
     */
    protected function resolveVariables(array $views, array $with): array
    {
        foreach ($views as &$view) {
            $view->contents = $this->substituteVariables(
                in: $view->contents,
                with: $with
            );
        }

        return $views;
    }

    /**
     * Recursively resolve nested arrays containing variable substitutions.
     */
    protected function substituteVariables(string $in, array $with): string
    {
        foreach ($with as $name => $value) {
            if (is_array($value)) {
                $in = $this->substituteVariables(
                    in: $in,
                    with: $value
                );
            } else {
                $in = str_replace(
                    search: "{{ {$name} }}",
                    replace: $value,
                    subject: $in
                );
            }
        }

        return trim($in);
    }

    /**
     * Export rendered views to the export location.
     *
     * @param array<string, View> $views
     */
    private function exportViews(array $views): void
    {
        foreach ($views as $view) {
            file_put_contents(
                filename: self::PAGES_DIST_DIRECTORY . "/{$view->name}.html",
                data: $view->contents
            );
        }
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
            message: 'Compile static GitHub Pages site'
        );

        $this->output->msg(
            group: OutputGroup::VIEWS,
            message: 'Compiling views...'
        );

        $calendarManifest = $this->getCalendarManifest();

        $viewsToExport = $this->render(
            with: [
                'app_url' => 'https://gocalendar.info/',
                'logo_url' => 'art/icon.svg',
                'calendars' => $calendarManifest,
                'default_calendar_url' => $calendarManifest['everything']['url'],
            ]
        );

        $this->exportViews(
            views: $viewsToExport
        );

        $this->output->msg(
            group: OutputGroup::VIEWS,
            message: 'Compiled!'
        );

        $this->output->msg(
            group: OutputGroup::END,
            message: 'GitHub Pages site compiled!'
        );

        return Command::SUCCESS;
    }
}
