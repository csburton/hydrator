<?php

namespace Pantono\Hydrator\Tests\MockObjects;

use Pantono\Hydrator\Attributes\Locator;
use Pantono\Hydrator\Attributes\FieldName;
use Pantono\Hydrator\Attributes\Lazy;

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
