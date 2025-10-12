<?php

namespace App\Core;

use Closure;
use ReflectionClass;
use ReflectionParameter;
use Exception;

class Container
{
    protected array $bindings = [];
    protected array $instances = [];

    public function register(string $abstract, Closure $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
    }

    public function singleton(string $abstract, Closure $concrete): void
    {
        $this->bindings[$abstract] = function ($container) use ($concrete) {
            if (!isset($this->instances[$abstract])) {
                $this->instances[$abstract] = $concrete($container);
            }
            return $this->instances[$abstract];
        };
    }

    public function resolve(string $abstract)
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (isset($this->bindings[$abstract])) {
            $concrete = $this->bindings[$abstract];
            return $concrete($this);
        }

        return $this->build($abstract);
    }

    protected function build(string $concrete)
    {
        $reflector = new ReflectionClass($concrete);

        if (!$reflector->isInstantiable()) {
            throw new Exception("Class {$concrete} is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $concrete;
        }

        $dependencies = $constructor->getParameters();
        $instances = $this->resolveDependencies($dependencies);

        return $reflector->newInstanceArgs($instances);
    }

    protected function resolveDependencies(array $dependencies): array
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            $type = $dependency->getType();

            if (!$type || $type->isBuiltin()) {
                 if ($dependency->isDefaultValueAvailable()) {
                    $results[] = $dependency->getDefaultValue();
                    continue;
                }
                throw new Exception("Cannot resolve untyped or built-in dependency: {$dependency->name}");
            }

            $typeName = $type->getName();
            $results[] = $this->resolve($typeName);
        }

        return $results;
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }
}
