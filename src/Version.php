<?php
declare(strict_types=1);

namespace Lethe;

final class Version
{
    public const STRING = '0.2';

    public static function get(): string
    {
        return self::STRING;
    }
}
