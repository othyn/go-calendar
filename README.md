# GO Calendar

[![Generate iCal](https://github.com/othyn/go-calendar/actions/workflows/generate.yml/badge.svg)](https://github.com/othyn/go-calendar/actions/workflows/generate.yml)
[![Lint](https://github.com/othyn/go-calendar/actions/workflows/lint.yml/badge.svg)](https://github.com/othyn/go-calendar/actions/workflows/lint.yml)
[![Deploy site to Pages](https://github.com/othyn/go-calendar/actions/workflows/pages.yml/badge.svg)](https://github.com/othyn/go-calendar/actions/workflows/pages.yml)
![GitHub all releases](https://img.shields.io/github/downloads/othyn/go-calendar/total?color=success&label=Downloads)

An automated unofficial iCal calendar generator for Pokémon GO events powered by Leek Duck, so you can easily have a
Pokémon GO calendar on your phone (iOS & Android) or computer (Linux, macOS & Windows) that is always up-to-date.

Take a look at [the website](https://gocalendar.info/) to get setup with the calendar.

# Sources & Credit

All events are sourced from [Leek Duck](https://leekduck.com/events/)
via [bigfoott/ScrapedDuck](https://github.com/bigfoott/ScrapedDuck), using
the [events JSON resource](https://github.com/bigfoott/ScrapedDuck/wiki/Events).

This project just aims to take all that brilliant hard work and dedication from the Leek Duck team, that is presented
oh-so nicely on the Leek Duck website, and create a highly convenient and easily consumable auto-updating iCal calendar
for use in any modern calendar client.

# Development

Project is just a simple PHP CLI script using a few Symfony libraries for the sake of convenience, not the best and far
from optimal, but fast and easy to maintain. The idea is to just have it get executed every 24 hours by a GitHub Action
workflow, in which it updates the output fragment of `./dist/gocal.ics` and commits it back to the `main` repo branch.
This keeps the URL consistent so in theory calendar clients should be able to subscribe to it.

There is a makefile in the root of the project that does most of the heavy lifting. A brief overview of the current
makefile:

```shell
# Build the relevant Docker containers for the project
make build

# Bring up/define in waiting the Docker containers for the project in detached mode
make up

# Tear down the running containers 
make down

# Tear down and bring back up the containers for the project, shortcut for:
# $ make down && make up
make restart

# Install the project dependencies via the project container
make install

# Lint the projects code via the project container
make lint

# Generate the calendar file into dist/gocal.ics via the project container 
make gen
```

As for the code itself, `bin/gocal` is the entrypoint of this CLI tool. It will bootstrap the Symfony console library
and pull in the instructed commands from `src/Commands`.

Hopefully the code should be clear enough to be self documenting, although I'm not usually one to rely on such things
alone, given the relative size of the project and associated debug output lines enclosing code blocks, it should be fine
in this circumstance. If the project grows any larger, then I'll produce the appropriate documentation to support it.

## A note on time zones and local time

This was a pain in the arse to solve, and I went through a lot of iterations coming up with a working solution. I had to
get my hands dirty and go digging, looking at the generated date output formats in the iCal file and delve into the
Spatie iCal library source to figure out what it was doing that it shouldn't be.

The first clue was in the generated dates in the output iCal file, any start and end dates that also had times were
suffixed with a `Z`. According to this [SO post](https://stackoverflow.com/a/7626131/4494375), any start and end dates
with times that end with `Z` will be interpreted in a UTC timezone, regardless of the timezone - or lack thereof - in
the calendar or event.

Next step, we need to remove the `Z`.

Turns out in `\Spatie\IcalendarGenerator\Properties\DateTimeProperty::getValue` if the DateTime value passed in is in
UTC, regardless of the state of `withoutTimezone()`, it will append `Z` to the generated Timezone, which will set the
Timezone to UTC in the calendar, even though we aren't specifying a timezone.
The [Spatie documentation](https://github.com/spatie/icalendar-generator#timezones) doesn't specify this behaviour, and
I believe this to be a bug in the library. For example, this is the current code:

```php
public function getValue(): string
{
    return $this->isUTC() && $this->dateTimeValue->hasTime()
        ? "{$this->dateTimeValue->format()}Z"
        : $this->dateTimeValue->format();
}
```

and this is how I think it should be:

```php
// Expose the $withoutTimeZone captured in the __construct as a class property
private bool $withoutTimeZone;

public function getValue(): string
{
    return $this->isUTC() && $this->dateTimeValue->hasTime() && ! $this->withoutTimeZone
        ? "{$this->dateTimeValue->format()}Z"
        : $this->dateTimeValue->format();
}
```

This as we should only be adding the `Z` suffix if the time zone has been explicitly set, otherwise we are getting the
UTC time zone interpreted by default.

# Legal

All rights reserved by their respective owners.

This project is not officially affiliated with Pokémon GO and is intended to fall under Fair Use doctrine, similar to
any other informational site such as a wiki.

Pokémon and its trademarks are ©1995-2022 Nintendo, Creatures, and GAMEFREAK.

All images and names owned and trademarked by Nintendo, Niantic, The Pokémon Company, and GAMEFREAK are property of
their respective owners.
