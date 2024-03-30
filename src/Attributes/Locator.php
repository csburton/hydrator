<?php
declare(strict_types=1);

namespace Pantono\Hydrator\Attributes;

#[\Attribute]
class Locator
{
    public string $serviceName = '';
    public string $methodName = '';
    public string $className = '';

    public function __construct(string $serviceName = '', string $methodName = '', string $className = '')
    {
        $this->serviceName = $serviceName;
        $this->methodName = $methodName;
        $this->className = $className;
    }
}
