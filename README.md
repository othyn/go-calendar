# GO Cal

[![Build iCal](https://github.com/othyn/go-cal/actions/workflows/build.yml/badge.svg)](https://github.com/othyn/go-cal/actions/workflows/build.yml)
[![Lint](https://github.com/othyn/go-cal/actions/workflows/lint.yml/badge.svg)](https://github.com/othyn/go-cal/actions/workflows/lint.yml)

An automated unofficial iCal calendar generator for Pokémon GO events powered by Leek Duck.

All events are sourced from [Leek Duck](https://leekduck.com/events/)
via [bigfoott/ScrapedDuck](https://github.com/bigfoott/ScrapedDuck), using
the [events JSON resource](https://github.com/bigfoott/ScrapedDuck/blob/master/docs/EVENTS.md).

This project just aims to take all that brilliant hard work and dedication from the Leek Duck team that is presented so
nicely on the Leek Duck website and create a highly convenient and easily consumable auto updating iCal calendar for use
in any modern calendar client.

# How To Calendar

TBC - I'm hoping I can just generate a static link to the raw version of the dist ics.

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
