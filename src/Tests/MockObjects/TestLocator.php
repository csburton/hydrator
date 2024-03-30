<?php

namespace Pantono\Hydrator\Tests\MockObjects;

use Pantono\Contracts\Locator\LocatorInterface;

class TestLocator implements LocatorInterface
{
    public function loadDependency(string $dependency): mixed
    {
        // TODO: Implement loadDependency() method.
    }

    public function getClassAutoWire(string $className): mixed
    {
        // TODO: Implement getClassAutoWire() method.
    }

    public function lookupRecord(string $className, mixed $id): mixed
    {
        // TODO: Implement lookupRecord() method.
    }

    public function loadClass(string $className, array $dependencies): mixed
    {
        // TODO: Implement loadClass() method.
    }
}
