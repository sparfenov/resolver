<?php declare(strict_types = 1);

namespace Acelot\Resolver\Tests\Functional;

use Acelot\Resolver\Definition\{
    ObjectDefinition, FactoryDefinition
};
use Acelot\Resolver\Resolver;
use Acelot\Resolver\Tests\Functional\Fixtures\{
    Config, ConfigFactory, Database, DatabaseFactory, Repository, RepositoryInterface, Service
};
use PHPUnit\Framework\TestCase;

class NestedDefinitionsTest extends TestCase
{
    public function testResolveClassWithNestedDependencies()
    {
        $resolver = new Resolver([
            Config::class => FactoryDefinition::define([ConfigFactory::class, 'create']),
            Database::class => FactoryDefinition::define([DatabaseFactory::class, 'create']),
            RepositoryInterface::class => ObjectDefinition::define(Repository::class)
        ]);

        /** @var Service $resolvedService */
        $resolvedService = $resolver->resolve(Service::class);
        self::assertInstanceOf(Service::class, $resolvedService);

        /** @var Repository $resolvedRepository */
        $resolvedRepository = $resolver->resolve(RepositoryInterface::class);
        self::assertSame($resolvedService->getRepository(), $resolvedRepository);

        /** @var Database $resolvedDatabase */
        $resolvedDatabase = $resolver->resolve(Database::class);
        self::assertSame($resolvedRepository->getDb(), $resolvedDatabase);
    }
}