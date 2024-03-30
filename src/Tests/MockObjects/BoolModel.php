<?php

namespace Pantono\Hydrator\Tests\MockObjects;

class BoolModel
{
    private bool $bool;

    public function isBool(): bool
    {
        return $this->bool;
    }

    public function setBool(bool $bool): void
    {
        $this->bool = $bool;
    }
}
