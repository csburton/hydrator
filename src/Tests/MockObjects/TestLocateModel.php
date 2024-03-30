<?php

namespace Pantono\Hydrator\Tests\MockObjects;

use Pantono\Hydrator\Attributes\Locator;

#[Locator(serviceName: '@Test', methodName: 'test')]
class TestLocateModel
{

}