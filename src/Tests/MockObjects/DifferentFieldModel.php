<?php

namespace Pantono\Hydrator\Tests\MockObjects;

use Pantono\Hydrator\Attributes\FieldName;

class DifferentFieldModel
{
    /**
     * @field other_data
     */
    #[FieldName('other_data')]
    private string $data;

    public function getData(): string
    {
        return $this->data;
    }

    public function setData(string $data): void
    {
        $this->data = $data;
    }
}
