<?php

namespace Pantono\Hydrator\Traits;

use Symfony\Component\HttpFoundation\Request;
use Pantono\Utilities\StringUtilities;
use Pantono\Utilities\ReflectionUtilities;
use Pantono\Utilities\DateTimeParser;

trait FillableTrait
{
    public static function fillFromRequest(Request $request): self
    {
        if ($request->getMethod() === 'POST' || $request->getMethod() === 'PUT') {
            $params = $request->request;
        } else {
            $params = $request->query;
        }
        $instance = new self;
        foreach (get_object_vars($instance) as $key => $value) {
            $model = ReflectionUtilities::parseAttributesIntoConfigModel(new \ReflectionProperty($instance, $key));
            $snakeCase = StringUtilities::snakeCase($key);
            $setter = 'set' . ucfirst($snakeCase);
            $hasSetter = method_exists($instance, $setter);
            if (!$hasSetter) {
                continue;
            }
            $value = null;
            if ($params->has($snakeCase)) {
                $value = $params->get($snakeCase);
            }
            if ($params->has($key)) {
                $value = $params->get($key);
            }
            if ($model->getType()) {
                if ($model->getType() === \DateTime::class || $model->getType() === \DateTimeInterface::class) {
                    $value = DateTimeParser::parseDate($value);
                }
                if ($model->getType() === \DateTimeImmutable::class) {
                    $value = DateTimeParser::parseDateImmutable($value);
                }
            }

            if ($value && $setter) {
                $instance->$setter($value);
            }
        }
        return $instance;
    }
}
