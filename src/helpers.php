<?php

declare(strict_types=1);

if (! function_exists(function: 'dd')) {
    function dd(mixed $var): void
    {
        var_dump($var);
        exit;
    }
}
