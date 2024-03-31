<?php

declare(strict_types=1);

namespace Pantono\Hydrator\Locator;

use Pantono\Contracts\Locator\LocatorInterface;

class StaticLocator
{
    private static ?LocatorInterface $locator = null;

    public static function setLocator(LocatorInterface $locator): void
    {
        self::$locator = $locator;
    }

    public static function getLocator(): LocatorInterface
    {
        if (self::$locator === null) {
            throw new \RuntimeException('Locator not set');
        }
        return self::$locator;
    }
}
