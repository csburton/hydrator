<?php

declare(strict_types=1);

namespace Pantono\Hydrator\Traits;

use Pantono\Hydrator\Locator\StaticLocator;
use Pantono\Contracts\Locator\LocatorInterface;

trait LocatorAwareTrait
{
    public function getLocator(): LocatorInterface
    {
        return StaticLocator::getLocator();
    }
}
