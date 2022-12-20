# GO Cal

[![Build iCal](https://github.com/othyn/go-cal/actions/workflows/build.yml/badge.svg)](https://github.com/othyn/go-cal/actions/workflows/build.yml)
[![Lint](https://github.com/othyn/go-cal/actions/workflows/lint.yml/badge.svg)](https://github.com/othyn/go-cal/actions/workflows/lint.yml)

An automated unofficial iCal calendar generator for Pokémon GO events powered by Leek Duck.

To get started, subscribe to this URL in your *favourite* calendar client:

```text
https://raw.githubusercontent.com/othyn/go-cal/main/dist/gocal.ics
```

Not sure how to do that? Here are some guides on how to subscribe to a calendar in some of the most popular calendar
clients:

- [Apple Calendar](https://support.apple.com/en-gb/HT202361)
    - See: "Set up a new iCloud calendar subscription on your Mac"
    - iOS, macOS and web
- [Google Calendar](https://support.google.com/calendar/answer/37100)
    - See: "Use a link to add a public calendar"
    - All platforms
- [Microsoft Outlook (web)](https://support.microsoft.com/en-us/office/import-or-subscribe-to-a-calendar-in-outlook-com-cff1429c-5af6-41ec-a5b4-74f2c278e98c)
    - See: "Subscribe to a calendar"
    - All platforms
- [Mozilla Lightning](https://support.mozilla.org/en-US/kb/adding-a-holiday-calendar#w_subscribe-to-it-on-the-internet)
    - See: "Subscribe to it on the internet"
    - All platforms

Don't see your calendar client? Submit a PR! All contributions are welcome.

Here is a [direct download of the iCal file](https://raw.githubusercontent.com/othyn/go-cal/main/dist/gocal.ics) if you
wish to download it instead of subscribing. You may have to right-click or long press on the link and 'Save as', as I
can't set the content type for the URL in markdown to let the browser do its thing automatically.

# Sources & Credit

All events are sourced from [Leek Duck](https://leekduck.com/events/)
via [bigfoott/ScrapedDuck](https://github.com/bigfoott/ScrapedDuck), using
the [events JSON resource](https://github.com/bigfoott/ScrapedDuck/blob/master/docs/EVENTS.md).

This project just aims to take all that brilliant hard work and dedication from the Leek Duck team, that is presented
oh-so nicely on the Leek Duck website, and create a highly convenient and easily consumable auto-updating iCal calendar
for use in any modern calendar client.

# Development

Project is just a simple PHP CLI script using a few Symfony libraries for the same of convenience, not the best and far
from optimal, but fast and easy to maintain. The idea is to just have it get executed every 24 hours by a GitHub Action
workflow, in which it updates the output fragment of `./dist/gocal.ics` and commits it back to the `main` repo branch.
This keeps the URL consistent so in theory calendar clients should be able to subscribe to it.

There is a makefile in the root of the project that does most of the heavy lifting. I've not yet containerised the
project as I've not had the need, although its easy enough to do as it will just need a PHP 8.1 container with a volume
map for the project directory. The makefile can then be updated to exec the build script from a disposable instance of
the container.

A brief overview of the current makefile:

```shell
# Install the project dependencies onto the host machine
make install

# Lint the projects code
make lint

# Run a build of the dist/gocal.ics calendar file
make build
```

As for the code itself, `bin/gocal` is the entrypoint of this CLI tool. It will bootstrap the Symfony console library
and pull in the instructed commands from `src/Commands`.

Hopefully the code should be clear enough to be self documenting, although I'm not usually one to rely on such things
alone, given the relative size of the project and associated debug output lines enclosing code blocks, it should be fine
in this circumstance. If the project grows any larger, then I'll produce the appropriate documentation to support it.

# Legal

All rights reserved by their respective owners.

This project is not officially affiliated with Pokémon GO and is intended to fall under Fair Use doctrine, similar to
any other informational site such as a wiki.

Pokémon and its trademarks are ©1995-2022 Nintendo, Creatures, and GAMEFREAK.

All images and names owned and trademarked by Nintendo, Niantic, The Pokémon Company, and GAMEFREAK are property of
their respective owners.
