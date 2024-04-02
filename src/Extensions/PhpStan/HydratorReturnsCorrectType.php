<?php

namespace Pantono\Hydrator\Extensions\PhpStan;

use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Analyser\Scope;
use PhpParser\Node\Expr\MethodCall;
use Pantono\Hydrator\Hydrator;
use PHPStan\Type\Type;

class HydratorReturnsCorrectType implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return Hydrator::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'hydrate';
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
    {
        $arg = $methodCall->getArgs()[0]->value;
        return $scope->getType($arg);
    }
}
