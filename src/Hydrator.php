<?php
declare(strict_types=1);

namespace Pantono\Hydrator;

use Pantono\Utilities\StringUtilities;
use Pantono\Utilities\DateTimeParser;
use Pantono\Utilities\ApplicationHelper;
use ReflectionNamedType;
use Pantono\Contracts\Container\ContainerInterface;
use Pantono\Contracts\Hydrator\HydratorInterface;
use Pantono\Hydrator\Attributes\FieldName;
use Pantono\Hydrator\Attributes\Locator;
use Pantono\Hydrator\Attributes\Filter;
use Pantono\Hydrator\Attributes\Lazy;

class Hydrator implements HydratorInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function hydrate(string $className, ?array $hydrateData = []): mixed
    {
        if ($hydrateData === null) {
            return null;
        }
        if (!class_exists($className)) {
            throw new \RuntimeException('Class ' . $className . ' does not exist for hydration');
        }
        $reflectionClass = new \ReflectionClass($className);
        $class = $reflectionClass->newInstance();
        if (empty($hydrateData)) {
            return null;
        }
        foreach ($reflectionClass->getProperties() as $property) {
            $info = $this->getHydratorFieldConfig($property);
            $field = $info['field_name'] ?: StringUtilities::snakeCase($property->getName());
            $data = $hydrateData[$field] ?? null;
            if ($data !== null || $field === '$this') {
                if ($info['lazy'] === true) {
                    continue;
                }
                if ($info['hydrator'] !== null) {
                    [$dependency, $method] = explode('::', $info['hydrator']);
                    if ($field === '$this') {
                        $data = $class;
                    }
                    $data = $this->container->getLocator()->loadDependency('@' . $dependency)->$method($data);
                } else {
                    $type = strtolower($info['type']);
                    if (str_starts_with($type, '?')) {
                        $type = substr($type, 1);
                    }
                    if ($info['type'] === 'int') {
                        $data = (int)$data;
                    }
                    if ($info['type'] === 'float') {
                        $data = (float)$data;
                    }
                    if ($info['type'] === 'bool') {
                        if ($data === 'yes') {
                            $data = true;
                        }
                        if ($data === 'no') {
                            $data = false;
                        }
                        $data = (bool)$data;
                    }
                    if (str_starts_with($type, '\\')) {
                        $type = substr($type, 1);
                    }
                    if ($type === 'datetime' || $type === 'datetimeinterface') {
                        if ($info['format'] !== null) {
                            $data = \DateTime::createFromFormat($info['format'], $data);
                        } else {
                            $data = DateTimeParser::parseDate($data);
                        }
                    }
                    if ($type === 'datetimeimmutable') {
                        if ($info['format'] !== null) {
                            $data = \DateTimeImmutable::createFromFormat($info['format'], $data);
                        } else {
                            $data = DateTimeParser::parseDateImmutable($data);
                        }
                    }
                }
                $setter = lcfirst(StringUtilities::camelCase('set' . ucfirst($property->getName())));
                if ($info['filter']) {
                    if ($info['filter'] === 'trim') {
                        $data = trim($data);
                    }
                    if ($info['filter'] === 'json_decode') {
                        $data = json_decode($data, true);
                    }
                    if ($info['filter'] === 'explode') {
                        if (!$data) {
                            $data = [];
                        } elseif (is_array($data)) {
                            $data = array_filter($data, function ($value) {
                                return $value !== '';
                            });
                        } else {
                            $data = strval($data);
                            $data = array_filter(explode(',', $data), function ($value) {
                                return $value !== '';
                            });
                        }
                    }
                    if ($info['filter'] === 'array_from_string') {
                        $data = $this->createArrayFromFieldString($data);
                    }
                }
                $hasSetter = $reflectionClass->hasMethod($setter);
                $parentHasSetter = $reflectionClass->hasMethod($setter);
                if (($hasSetter || $parentHasSetter) && $data !== null) {
                    $class->$setter($data);
                }
            }
        }

        return $class;
    }

    public function lookupRecord(string $className, mixed $field): mixed
    {
        if (!class_exists($className)) {
            throw new \RuntimeException('Class ' . $className . ' does not exist');
        }

        $class = new \ReflectionClass($className);
        $attributes = $class->getAttributes(Locator::class);
        if (empty($attributes)) {
            return null;
        }
        $args = $attributes[0]->getArguments();
        $service = $args['serviceName'];
        $methodName = $args['methodName'];

        return $this->container->getLocator()->loadDependency($service)->$methodName($field);
    }

    public function hydrateSet(string $className, array $data): array
    {
        $items = [];
        foreach ($data as $item) {
            $items[] = $this->hydrate($className, $item);
        }

        return $items;
    }

    /**
     * @param class-string $className
     * @throws \ReflectionException
     */
    private function createProxyClass(string $className): \ReflectionClass
    {
        $dir = ApplicationHelper::getApplicationRoot() . '/conf/cache/proxies/';
        if (!is_dir($dir)) {
            mkdir($dir);
        }
        $reflection = new \ReflectionClass($className);
        $filename = $reflection->getFileName();
        if (!$filename) {
            throw new \RuntimeException('Unable to get filename for ' . $className);
        }
        $cacheKey = md5(filemtime($filename) . $className . ApplicationHelper::getReleaseTimestamp());
        $target = $dir . $cacheKey . '.php';
        $proxyClassName = $reflection->getShortName() . 'ProxyClass';
        if (!file_exists($target)) {

            $proxyGenerator = new ProxyGenerator();
            $proxyClass = $proxyGenerator->generateProxyClass($className);

            file_put_contents($target, $proxyClass);
        }
        /**
         * @var class-string $className
         */
        $className = '\\Pantono\\Proxy\\' . $proxyClassName;
        require_once $target;

        return new \ReflectionClass($className);
    }


    /**
     * Create array from field string
     *
     * @param string $string Allowed values string
     *
     * @return array
     */
    private function createArrayFromFieldString(string $string): array
    {
        $fields = [];
        foreach (explode(',', $string) as $field) {
            if (str_contains($field, ':')) {
                [$key, $value] = explode(':', $field);
                $fields[$key] = $value;
            } else {
                $fields[] = $field;
            }
        }

        return array_filter($fields);
    }

    private function getHydratorFieldConfig(\ReflectionProperty $property): array
    {
        $info = [
            'type' => null,
            'hydrator' => null,
            'field_name' => StringUtilities::snakeCase($property->getName()),
            'filter' => null,
            'lazy' => null,
            'format' => null
        ];
        $type = $property->getType();
        if ($type instanceof ReflectionNamedType) {
            $info['type'] = $type->getName();
        }
        foreach ($property->getAttributes() as $attribute) {
            $instance = $attribute->newInstance();
            if (get_class($instance) === FieldName::class) {
                $info['field_name'] = $instance->name;
            }
            if (get_class($instance) === Filter::class) {
                $info['filter'] = $instance->filter;
            }
            if (get_class($instance) === Locator::class) {
                $info['hydrator'] = $instance->serviceName . '::' . $instance->methodName;
            }
            if (get_class($instance) === Lazy::class) {
                $info['lazy'] = true;
            }
        }

        return $info;
    }
}