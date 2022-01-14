<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Tests\Doctrine\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use Corerely\ApiPlatformHelperBundle\Doctrine\AbstractDoctrineExtensionTest;
use Corerely\ApiPlatformHelperBundle\Doctrine\Extension\IdentifierCollectionFilterExtension;
use Corerely\ApiPlatformHelperBundle\Tests\Factory\DummyFactory;
use Symfony\Component\Routing\RouterInterface;

class IdentifierCollectionFilterExtensionTest extends AbstractDoctrineExtensionTest
{
    public function testFilterWithId(): void
    {
        DummyFactory::createMany(3);

        $dummy = DummyFactory::createOne();
        $id = $dummy->getId();

        $mockedRouter = $this->createMock(RouterInterface::class);
        $mockedRouter->expects($this->once())->method('match')->with($id)->willReturn(
            ['_api_identifiers' => ['id'], 'id' => $id],
        );

        $ext = new IdentifierCollectionFilterExtension($mockedRouter);

        $queryBuilder = $this->repository->createQueryBuilder('o');
        $ext->applyToCollection($queryBuilder, new QueryNameGenerator(), $this->entityClassName, context: ['filters' => ['id' => (string)$id]]);

        $result = $queryBuilder->getQuery()->getResult();
        $this->assertCount(1, $result);
        $this->assertSame($dummy->object(), $result[0]);
    }

    public function testFilterWithIds(): void
    {
        DummyFactory::createMany(3);

        $dummy1 = DummyFactory::createOne();
        $dummy2 = DummyFactory::createOne();
        $id1 = $dummy1->getId();
        $id2 = $dummy2->getId();

        $mockedRouter = $this->createMock(RouterInterface::class);
        $mockedRouter->expects($this->exactly(2))->method('match')->willReturn(
            ['_api_identifiers' => ['id'], 'id' => $id1],
            ['_api_identifiers' => ['id'], 'id' => $id2],
        );

        $ext = new IdentifierCollectionFilterExtension($mockedRouter);

        $queryBuilder = $this->repository->createQueryBuilder('o');
        $ext->applyToCollection($queryBuilder, new QueryNameGenerator(), $this->entityClassName, context: ['filters' => ['id' => [(string)$id1, (string)$id2]]]);

        $result = $queryBuilder->getQuery()->getResult();
        $this->assertCount(2, $result);
        $this->assertSame($dummy1->object(), $result[0]);
        $this->assertSame($dummy2->object(), $result[1]);
    }

    public function testFilterWithUuid(): void
    {
        DummyFactory::createMany(3);

        $dummy = DummyFactory::createOne();
        $uuid = (string)$dummy->getUuid();

        $mockedRouter = $this->createMock(RouterInterface::class);
        $mockedRouter->expects($this->once())->method('match')->with($uuid)->willReturn(
            ['_api_identifiers' => ['uuid'], 'uuid' => $uuid],
        );

        $ext = new IdentifierCollectionFilterExtension($mockedRouter);

        $queryBuilder = $this->repository->createQueryBuilder('o');
        $ext->applyToCollection($queryBuilder, new QueryNameGenerator(), $this->entityClassName, context: ['filters' => ['id' => $uuid]]);

        $result = $queryBuilder->getQuery()->getResult();
        $this->assertCount(1, $result);
        $this->assertSame($dummy->object(), $result[0]);
    }

    public function testFilterWithUuids(): void
    {
        DummyFactory::createMany(3);

        $dummy1 = DummyFactory::createOne();
        $dummy2 = DummyFactory::createOne();
        $uuid1 = (string)$dummy1->getUuid();
        $uuid2 = (string)$dummy2->getUuid();

        $mockedRouter = $this->createMock(RouterInterface::class);
        $mockedRouter->expects($this->exactly(2))->method('match')->willReturn(
            ['_api_identifiers' => ['uuid'], 'uuid' => $uuid1],
            ['_api_identifiers' => ['uuid'], 'uuid' => $uuid2],
        );

        $ext = new IdentifierCollectionFilterExtension($mockedRouter);

        $queryBuilder = $this->repository->createQueryBuilder('o');
        $ext->applyToCollection($queryBuilder, new QueryNameGenerator(), $this->entityClassName, context: ['filters' => ['id' => [$uuid1, $uuid2]]]);

        $result = $queryBuilder->getQuery()->getResult();
        $this->assertCount(2, $result);
        $this->assertSame($dummy1->object(), $result[0]);
        $this->assertSame($dummy2->object(), $result[1]);
    }
}
