<?php

declare(strict_types=1);

namespace Console\Enums;

enum OutputGroup: string
{
    case START = 'START';
    case SOURCE = 'SOURCE';
    case PARSE = 'PARSE';
    case CALENDAR = 'CALENDAR';
    case EVENTS = 'EVENTS';
    case EVENT = 'EVENT';
    case EXPORT = 'EXPORT';
    case EXPORTSUB = 'EXPORT-SUB';
    case VIEWS = 'VIEWS';
    case END = 'END';

    public function prefix(): string
    {
        return match ($this) {
            self::START => '┌<',
            self::SOURCE => '├',
            self::PARSE => '├',
            self::CALENDAR => '├',
            self::EVENTS => '├',
            self::EVENT => '├',
            self::EXPORT => '├',
            self::EXPORTSUB => '├',
            self::VIEWS => '├',
            self::END => '└<'
        };
    }

    public function indent(): int
    {
        return match ($this) {
            self::START => 0,
            self::SOURCE => 1,
            self::PARSE => 1,
            self::CALENDAR => 1,
            self::EVENTS => 1,
            self::EVENT => 2,
            self::EXPORT => 1,
            self::EXPORTSUB => 2,
            self::VIEWS => 1,
            self::END => 0
        };
    }
}
