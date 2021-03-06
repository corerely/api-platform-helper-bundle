<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Tests\Doctrine\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use Corerely\ApiPlatformHelperBundle\Doctrine\Filter\UuidFilter;
use Corerely\ApiPlatformHelperBundle\Tests\Doctrine\AbstractDoctrineExtensionTest;
use Corerely\ApiPlatformHelperBundle\Tests\Factory\DummyAssociationFactory;
use Corerely\ApiPlatformHelperBundle\Tests\Factory\DummyFactory;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Uid\UuidV4;

class UuidFilterTest extends AbstractDoctrineExtensionTest
{
    public function testFilterByUuid(): void
    {
        DummyFactory::createMany(3);

        $dummy = DummyFactory::createOne(['uuid' => UuidV4::v4()]);
        $uuid = (string)$dummy->getUuid();

        DummyFactory::assert()->count(4);

        $mockedRouter = $this->createMock(RouterInterface::class);
        $mockedRouter->expects($this->once())->method('match')->with('/api/' . $uuid)->willReturn(
            ['_api_identifiers' => ['uuid'], 'uuid' => $uuid],
        );

        $queryBuilder = $this->repository->createQueryBuilder('o');
        $filter = new UuidFilter($mockedRouter, $this->managerRegistry, properties: ['uuid' => null]);
        $filter->apply($queryBuilder, new QueryNameGenerator(), $this->entityClassName, context: ['filters' => ['uuid' => '/api/' . $uuid]]);

        $result = $queryBuilder->getQuery()->getResult();
        $this->assertCount(1, $result);
        $this->assertSame($dummy->getId(), $result[0]->getId());
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

        $mockedRouter = $this->createMock(RouterInterface::class);
        $mockedRouter->expects($this->exactly(2))->method('match')->willReturn(
            ['_api_identifiers' => ['uuid'], 'uuid' => $uuid1],
            ['_api_identifiers' => ['uuid'], 'uuid' => $uuid2],
        );

        $queryBuilder = $this->createQueryBuilder();
        $filter = new UuidFilter($mockedRouter, $this->managerRegistry, properties: ['dummyAssociations' => null]);
        $filter->apply($queryBuilder, new QueryNameGenerator(), $this->entityClassName, context: ['filters' => ['dummyAssociations' => ['/api/' . $uuid1, '/api/' . $uuid2]]]);

        $result = $queryBuilder->orderBy('o.id', 'asc')->getQuery()->getResult();
        $this->assertCount(2, $result);
        $this->assertSame($dummy1->getId(), $result[0]->getId());
        $this->assertSame($dummy2->getId(), $result[1]->getId());
    }
}
