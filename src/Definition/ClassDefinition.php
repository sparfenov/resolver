<?php declare(strict_types = 1);

namespace Acelot\Resolver\Definition;

use Acelot\Resolver\Definition\Traits\ArgumentsTrait;
use Acelot\Resolver\DefinitionInterface;
use Acelot\Resolver\Exception\ResolverException;
use Acelot\Resolver\ResolverInterface;
use Psr\SimpleCache\CacheInterface;

class ClassDefinition implements DefinitionInterface
{
    use ArgumentsTrait;

    /**
     * @var string
     */
    protected $fqcn;

    /**
     * Creates the definition with given class name.
     *
     * @param string $fqcn Fully qualified class name
     *
     * @return ClassDefinition
     */
    public static function define(string $fqcn): ClassDefinition
    {
        return new ClassDefinition($fqcn);
    }

    /**
     * @param string $fqcn Fully qualified class name
     */
    private function __construct(string $fqcn)
    {
        $this->fqcn = $fqcn;
    }

    /**
     * Returns the fully qualified class name.
     *
     * @return string
     */
    public function getFqcn(): string
    {
        return $this->fqcn;
    }

    /**
     * Resolves and returns the instance of the class.
     *
     * @param ResolverInterface $resolver
     * @param CacheInterface    $cache
     *
     * @return object
     * @throws ResolverException
     */
    public function resolve(ResolverInterface $resolver, CacheInterface $cache)
    {
        try {
            $ref = new \ReflectionClass($this->getFqcn());
        } catch (\ReflectionException $e) {
            throw new ResolverException(sprintf('The class "%s" does not exists', $this->getFqcn()));
        }

        $factoryMethod = $ref->getConstructor();
        if ($factoryMethod === null) {
            return $ref->newInstance();
        }

        $args = [];

        foreach ($factoryMethod->getParameters() as $param) {
            if ($this->hasArgument($param->getName())) {
                $args[] = $this->getArgument($param->getName());
                continue;
            }

            if ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
                continue;
            }

            $paramClass = $param->getClass();
            if ($paramClass !== null) {
                $args[] = $resolver->resolve($paramClass->getName());
                continue;
            }

            throw new ResolverException(
                sprintf('Cannot resolve the class "%s"', $ref->getName())
            );
        }

        return $ref->newInstanceArgs($args);
    }
}