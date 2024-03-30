<?php

namespace Pantono\Hydrator\Tests\MockObjects;


use Pantono\Contracts\Container\ContainerInterface;
use Pantono\Contracts\Locator\LocatorInterface;

class TestContainer implements ContainerInterface
{
    private array $items = [];

    public function get(string $id)
    {
        return $this->items[$id] ?? null;
    }

    public function has(string $id): bool
    {
        return isset($this->items[$id]);
    }

    public function set(string $id, mixed $value)
    {
        $this->items[$id] = $value;
    }

    public function getLocator(): LocatorInterface
    {
        return new TestLocator();
    }
}
