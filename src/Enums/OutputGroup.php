<?php

declare(strict_types=1);

namespace Console\Enums;

enum OutputGroup: string
{
    case START = 'START';
    case SOURCE = 'SOURCE';
    case CALENDAR = 'CALENDAR';
    case EVENTS = 'EVENTS';
    case EXPORT = 'EXPORT';
    case VIEWS = 'VIEWS';
    case END = 'END';

    public function prefix(): string
    {
        return match ($this) {
            self::START => '┌<',
            self::SOURCE => '├',
            self::CALENDAR => '├',
            self::EVENTS => '├',
            self::EXPORT => '├',
            self::VIEWS => '├',
            self::END => '└<'
        };
    }

    public function indent(): int
    {
        return match ($this) {
            self::START => 0,
            self::SOURCE => 1,
            self::CALENDAR => 1,
            self::EVENTS => 1,
            self::EXPORT => 1,
            self::VIEWS => 1,
            self::END => 0
        };
    }
}
