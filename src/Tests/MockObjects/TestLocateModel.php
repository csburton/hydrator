<?php

namespace Pantono\Hydrator\Tests\MockObjects;

use Pantono\Contracts\Attributes\Locator;

#[Locator(serviceName: '@Test', methodName: 'test')]
class TestLocateModel
{
}
