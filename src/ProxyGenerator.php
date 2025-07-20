<?php

namespace Pantono\Hydrator;

use Nette\PhpGenerator\PhpNamespace;
use Pantono\Contracts\Application\Proxy\ProxyInterface;
use Pantono\Utilities\StringUtilities;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PsrPrinter;
use ReflectionNamedType;
use Pantono\Hydrator\Traits\LocatorAwareTrait;
use Pantono\Utilities\ReflectionUtilities;

class ProxyGenerator
{
    public function generateProxyClass(string $className): string
    {
        if (!class_exists($className)) {
            throw new \RuntimeException('Class ' . $className . ' does not exist');
        }

        $reflection = new \ReflectionClass($className);
        $namespace = new PhpNamespace('Pantono\Proxy');
        $class = $namespace->addClass($reflection->getShortName() . 'ProxyClass');
        $class->setExtends($className);
        $namespace->addUse(LocatorAwareTrait::class);
        $class->addTrait(LocatorAwareTrait::class);
        $namespace->addUse($className);
        $namespace->addUse(ProxyInterface::class);
        $class->addImplement(ProxyInterface::class);

        $class->addProperty('hydratorParams')->setType('array')->setValue([])->setVisibility('private');
        $class->addProperty('completedLookups')->setType('array')->setValue([])->setVisibility('private');

        $getterMethod = $class->addMethod('setHydratorParams');
        $getterMethod->setReturnType('void');
        $getterMethod->addParameter('params')->setType('array');
        $getterMethod->setBody('$this->hydratorParams = $params;');

        foreach ($reflection->getProperties() as $property) {
            $lazy = false;
            $hydrator = null;
            $config = ReflectionUtilities::parseAttributesIntoConfig($property);
            if (isset($config['lazy'])) {
                $lazy = $config['lazy'];
            }
            if (isset($config['hydrator'])) {
                $hydrator = $config['hydrator'];
            }
            $fieldName = $config['field_name'];

            if ($lazy === true && $hydrator !== null && $fieldName) {
                $getter = lcfirst(StringUtilities::camelCase('get' . ucfirst($property->getName())));
                $setter = lcfirst(StringUtilities::camelCase('set' . ucfirst($property->getName())));
                [$lookupDependency, $lookupMethod] = explode('::', $hydrator);
                if ($fieldName === '$this') {
                    $lookupValue = '$this';
                } else {
                    $lookupValue = "\$this->hydratorParams['$fieldName']";
                }
                $getterMethod = $this->cloneMethod($reflection->getMethod($getter), $class, $namespace);
                $body = <<<METHOD_BODY
global \$app;
\$parentValue = parent::$getter();
if (isset(\$this->completedLookups['$getter'])) {
    return \$parentValue;
}
\$this->completedLookups['$getter'] = true;
\$value = \$this->getLocator()->getClassAutowire('$lookupDependency')->$lookupMethod($lookupValue); 
if (\$value) {
    parent::{$setter}(\$value);
}
return parent::{$getter}();
METHOD_BODY;
                $getterMethod->setBody($body);

                if ($reflection->hasMethod($setter)) {
                    $sourceSetter = $reflection->getMethod($setter);
                    $method = $this->cloneMethod($sourceSetter, $class, $namespace);

                    $paramName = array_keys($method->getParameters())[0];
                    $setterBody = <<<SETTER_BODY
parent::$setter(\${$paramName});
\$this->completedLookups['$getter'] = true;
SETTER_BODY;
                    $method->setBody($setterBody);
                }
            }
        }
        $printer = new PsrPrinter();
        return '<?php' . PHP_EOL . $printer->printNamespace($namespace);
    }

    private function cloneMethod(\ReflectionMethod $reflectionMethod, ClassType $class, PhpNamespace $namespace): Method
    {
        $method = $class->addMethod($reflectionMethod->getName());
        if ($reflectionMethod->isPrivate()) {
            $method->setVisibility('private');
        } elseif ($reflectionMethod->isProtected()) {
            $method->setVisibility('protected');
        } elseif ($reflectionMethod->isPublic()) {
            $method->setVisibility('public');
        }
        if ($reflectionMethod->getReturnType() instanceof ReflectionNamedType) {
            $returnType = $reflectionMethod->getReturnType()->getName();
            if (str_contains($returnType, '\\')) {
                if (substr($returnType, 0, 1) === '?') {
                    $returnType = substr($returnType, 1);
                }
                $namespace->addUse($returnType);
            }
            $method->setReturnType($reflectionMethod->getReturnType()->getName());
        }
        foreach ($reflectionMethod->getParameters() as $parameter) {
            $method->addParameter(
                $parameter->getName()
            )->setType($parameter->getType())->setNullable($parameter->allowsNull());
        }
        return $method;
    }
}
