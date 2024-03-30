<?php
declare(strict_types=1);
namespace Pantono\Hydrator\Attributes;

use Attribute;

#[Attribute]
class Filter
{
    public string $filter = '';

    public function __construct(string $filter)
    {
        $this->filter = $filter;
    }
}