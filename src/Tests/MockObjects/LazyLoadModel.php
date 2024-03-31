<?php

namespace Pantono\Hydrator\Tests\MockObjects;

use Pantono\Contracts\Attributes\Locator;
use Pantono\Contracts\Attributes\FieldName;
use Pantono\Contracts\Attributes\Lazy;

class LazyLoadModel
{
    #[Locator('SomeClass', 'getSomeMethod'), FieldName('test_field'), Lazy]
    private BoolModel $test;

    public function getTest(): BoolModel
    {
        return $this->test;
    }

    public function setTest(BoolModel $test): void
    {
        $this->test = $test;
    }
}
