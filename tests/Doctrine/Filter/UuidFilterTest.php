<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Tests\Doctrine\Filter;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\IriConverterInterface;
use Corerely\ApiPlatformHelperBundle\Doctrine\Filter\UuidFilter;
use Corerely\ApiPlatformHelperBundle\Test\FactoriesProxyHelper;
use Corerely\ApiPlatformHelperBundle\Tests\Doctrine\AbstractDoctrineExtension;
use Corerely\ApiPlatformHelperBundle\Tests\Factory\DummyAssociationFactory;
use Corerely\ApiPlatformHelperBundle\Tests\Factory\DummyFactory;
use Symfony\Component\Uid\UuidV4;

class UuidFilterTest extends AbstractDoctrineExtension
{
    use FactoriesProxyHelper;

    public function testFilterByUuid(): void
    {
        DummyFactory::createMany(3);

        $dummy = DummyFactory::createOne(['uuid' => UuidV4::v4()]);
        $uuid = (string)$dummy->getUuid();

        DummyFactory::assert()->count(4);

        $mockIriConverter = $this->createMock(IriConverterInterface::class);
        $mockIriConverter->expects($this->once())->method('getResourceFromIri')->with('/api/' . $uuid)->willReturn($this->getRealEntityObject($dummy));

        $queryBuilder = $this->repository->createQueryBuilder('o');
        $filter = new UuidFilter($mockIriConverter, $this->managerRegistry, properties: ['uuid' => null]);
        $filter->apply($queryBuilder, new QueryNameGenerator(), $this->entityClassName, context: ['filters' => ['uuid' => '/api/' . $uuid]]);

        $result = $queryBuilder->getQuery()->getResult();
        self::assertCount(1, $result);
        self::assertSame($dummy->getId(), $result[0]->getId());
    }

    public function testFilterByUuidsAssociation(): void
    {
        DummyFactory::new()->withAssociations()->many(3)->create();

        $dummy1 = DummyFactory::createOne([
            'dummyAssociations' => DummyAssociationFactory::createMany(1, ['uuid' => UuidV4::v4()]),
        ]);
        $uuid1 = (string)$dummy1->getDummyAssociations()->first()->getUuid();

        $dummy2 = DummyFactory::createOne([
            'dummyAssociations' => DummyAssociationFactory::createMany(1, ['uuid' => UuidV4::v4()]),
        ]);
        $uuid2 = (string)$dummy2->getDummyAssociations()->first()->getUuid();

        DummyFactory::assert()->count(5);

        $mockIriConverter = $this->createMock(IriConverterInterface::class);
        $mockIriConverter->expects($this->exactly(2))->method('getResourceFromIri')->willReturn(
            $dummy1->getDummyAssociations()->first(),
            $dummy2->getDummyAssociations()->first(),
        );

        $queryBuilder = $this->createQueryBuilder();
        $filter = new UuidFilter($mockIriConverter, $this->managerRegistry, properties: ['dummyAssociations' => null]);
        $filter->apply($queryBuilder, new QueryNameGenerator(), $this->entityClassName, context: ['filters' => ['dummyAssociations' => ['/api/' . $uuid1, '/api/' . $uuid2]]]);

        $result = $queryBuilder->orderBy('o.id', 'asc')->getQuery()->getResult();
        self::assertCount(2, $result);
        self::assertSame($dummy1->getId(), $result[0]->getId());
        self::assertSame($dummy2->getId(), $result[1]->getId());
    }
}
