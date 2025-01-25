<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Tests\Doctrine\Extension;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\IriConverterInterface;
use Corerely\ApiPlatformHelperBundle\Doctrine\Extension\IdentifierCollectionFilterExtension;
use Corerely\ApiPlatformHelperBundle\Doctrine\IdentifierMode;
use Corerely\ApiPlatformHelperBundle\Tests\Doctrine\AbstractDoctrineExtension;
use Corerely\ApiPlatformHelperBundle\Tests\Factory\DummyFactory;

class IdentifierCollectionFilterExtensionTest extends AbstractDoctrineExtension
{

    public function testFilterWithIriId(): void
    {
        DummyFactory::createMany(3);

        $dummy = DummyFactory::createOne();
        $iri = '/api/dummy/'.$dummy->getId();

        DummyFactory::assert()->count(4);

        $mockIriConverter = $this->createMock(IriConverterInterface::class);
        $mockIriConverter->expects($this->once())->method('getResourceFromIri')->with($iri)->willReturn($dummy);

        $filterExtension = new IdentifierCollectionFilterExtension($mockIriConverter, IdentifierMode::ID);

        $queryBuilder = $this->repository->createQueryBuilder('o');
        $filterExtension->applyToCollection($queryBuilder, new QueryNameGenerator(), $this->entityClassName, context: ['filters' => ['id' => $iri]]);

        $result = $queryBuilder->getQuery()->getResult();

        self::assertCount(1, $result);
        self::assertSame($dummy->getId(), $result[0]->getId());
    }

    public function testFilterWithNumbericIriId(): void
    {
        DummyFactory::createMany(3);

        $dummy = DummyFactory::createOne();
        $id = (string) $dummy->getId();

        DummyFactory::assert()->count(4);

        $mockIriConverter = $this->createMock(IriConverterInterface::class);
        $mockIriConverter->expects($this->never())->method('getResourceFromIri');

        $filterExtension = new IdentifierCollectionFilterExtension($mockIriConverter, IdentifierMode::ID);

        $queryBuilder = $this->repository->createQueryBuilder('o');
        $filterExtension->applyToCollection($queryBuilder, new QueryNameGenerator(), $this->entityClassName, context: ['filters' => ['id' => $id]]);

        $result = $queryBuilder->getQuery()->getResult();

        self::assertCount(1, $result);
        self::assertSame($dummy->getId(), $result[0]->getId());
    }

    public function testFilterWithIriIds(): void
    {
        DummyFactory::createMany(3);

        $dummy1 = DummyFactory::createOne();
        $dummy2 = DummyFactory::createOne();

        $iri1 = '/api/dummy/'.$dummy1->getId();
        $iri2 = '/api/dummy/'.$dummy2->getId();

        DummyFactory::assert()->count(5);

        $mockIriConverter = $this->createMock(IriConverterInterface::class);
        $mockIriConverter->expects($this->exactly(2))->method('getResourceFromIri')->willReturnOnConsecutiveCalls($dummy1, $dummy2);

        $filterExtension = new IdentifierCollectionFilterExtension($mockIriConverter, IdentifierMode::ID);

        $queryBuilder = $this->createQueryBuilder();
        $filterExtension->applyToCollection($queryBuilder, new QueryNameGenerator(), $this->entityClassName, context: ['filters' => ['id' => [$iri1, $iri2]]]);

        $result = $queryBuilder->orderBy('o.id', 'asc')->getQuery()->getResult();
        self::assertCount(2, $result);
        self::assertSame($dummy1->getId(), $result[0]->getId());
        self::assertSame($dummy2->getId(), $result[1]->getId());
    }

    public function testFilterWithNoFilters(): void
    {
        DummyFactory::createMany(3);

        DummyFactory::assert()->count(3);

        $mockIriConverter = $this->createMock(IriConverterInterface::class);
        $mockIriConverter->expects($this->never())->method('getResourceFromIri');

        $filterExtension = new IdentifierCollectionFilterExtension($mockIriConverter, IdentifierMode::ID);

        $queryBuilder = $this->createQueryBuilder();
        $filterExtension->applyToCollection($queryBuilder, new QueryNameGenerator(), $this->entityClassName);

        $result = $queryBuilder->orderBy('o.id', 'asc')->getQuery()->getResult();
        self::assertCount(3, $result);
    }
}
