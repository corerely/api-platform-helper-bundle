<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Tests\Doctrine\Extension;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use Corerely\ApiPlatformHelperBundle\Doctrine\Extension\IdentifierCollectionFilterExtension;
use Corerely\ApiPlatformHelperBundle\Tests\Doctrine\AbstractDoctrineExtensionTest;
use Corerely\ApiPlatformHelperBundle\Tests\Factory\DummyFactory;
use Symfony\Component\Routing\RouterInterface;

class IdentifierCollectionFilterExtensionTest extends AbstractDoctrineExtensionTest
{
    public function testFilterWithId(): void
    {
        DummyFactory::createMany(3);

        $dummy = DummyFactory::createOne();
        $id = $dummy->getId();
        $iri = '/api/' . $id;

        DummyFactory::assert()->count(4);

        $mockedRouter = $this->createMock(RouterInterface::class);
        $mockedRouter->expects($this->once())->method('match')->with($iri)->willReturn(
            ['_api_identifiers' => ['id'], 'id' => $id],
        );

        $ext = new IdentifierCollectionFilterExtension($mockedRouter);

        $queryBuilder = $this->repository->createQueryBuilder('o');
        $ext->applyToCollection($queryBuilder, new QueryNameGenerator(), $this->entityClassName, context: ['filters' => ['id' => $iri]]);

        $result = $queryBuilder->getQuery()->getResult();
        $this->assertCount(1, $result);
        $this->assertSame($dummy->getId(), $result[0]->getId());
    }

    public function testFilterWithIds(): void
    {
        DummyFactory::createMany(3);

        $dummy1 = DummyFactory::createOne();
        $dummy2 = DummyFactory::createOne();
        $id1 = $dummy1->getId();
        $id2 = $dummy2->getId();

        DummyFactory::assert()->count(5);

        $mockedRouter = $this->createMock(RouterInterface::class);
        $mockedRouter->expects($this->exactly(2))->method('match')->willReturn(
            ['_api_identifiers' => ['id'], 'id' => $id1],
            ['_api_identifiers' => ['id'], 'id' => $id2],
        );

        $ext = new IdentifierCollectionFilterExtension($mockedRouter);

        $queryBuilder = $this->createQueryBuilder();
        $ext->applyToCollection($queryBuilder, new QueryNameGenerator(), $this->entityClassName, context: ['filters' => ['id' => ['/api/' . $id1, '/api/' . $id2]]]);

        $result = $queryBuilder->orderBy('o.id', 'asc')->getQuery()->getResult();
        $this->assertCount(2, $result);
        $this->assertSame($dummy1->getId(), $result[0]->getId());
        $this->assertSame($dummy2->getId(), $result[1]->getId());
    }

    public function testFilterWithUuid(): void
    {
        DummyFactory::createMany(3);

        $dummy = DummyFactory::createOne();
        $uuid = (string)$dummy->getUuid();

        DummyFactory::assert()->count(4);

        $mockedRouter = $this->createMock(RouterInterface::class);
        $mockedRouter->expects($this->once())->method('match')->with('/api/' . $uuid)->willReturn(
            ['_api_identifiers' => ['uuid'], 'uuid' => $uuid],
        );

        $ext = new IdentifierCollectionFilterExtension($mockedRouter);

        $queryBuilder = $this->repository->createQueryBuilder('o');
        $ext->applyToCollection($queryBuilder, new QueryNameGenerator(), $this->entityClassName, context: ['filters' => ['id' => '/api/' . $uuid]]);

        $result = $queryBuilder->getQuery()->getResult();
        $this->assertCount(1, $result);
        $this->assertSame($dummy->getId(), $result[0]->getId());
    }

    public function testFilterWithUuids(): void
    {
        DummyFactory::createMany(3);

        $dummy1 = DummyFactory::createOne();
        $dummy2 = DummyFactory::createOne();
        $uuid1 = (string)$dummy1->getUuid();
        $uuid2 = (string)$dummy2->getUuid();

        DummyFactory::assert()->count(5);

        $mockedRouter = $this->createMock(RouterInterface::class);
        $mockedRouter->expects($this->exactly(2))->method('match')->willReturn(
            ['_api_identifiers' => ['uuid'], 'uuid' => $uuid1],
            ['_api_identifiers' => ['uuid'], 'uuid' => $uuid2],
        );

        $ext = new IdentifierCollectionFilterExtension($mockedRouter);

        $queryBuilder = $this->createQueryBuilder();
        $ext->applyToCollection($queryBuilder, new QueryNameGenerator(), $this->entityClassName, context: ['filters' => ['id' => ['/api/' . $uuid1, '/api/' . $uuid2]]]);

        $result = $queryBuilder->orderBy('o.id', 'asc')->getQuery()->getResult();
        $this->assertCount(2, $result);
        $this->assertSame($dummy1->getId(), $result[0]->getId());
        $this->assertSame($dummy2->getId(), $result[1]->getId());
    }

    public function testFilterWithNumericId(): void
    {
        DummyFactory::createMany(3);

        $dummy = DummyFactory::createOne();
        $id = $dummy->getId();

        DummyFactory::assert()->count(4);

        $mockedRouter = $this->createMock(RouterInterface::class);
        $mockedRouter->expects($this->never())->method('match');

        $ext = new IdentifierCollectionFilterExtension($mockedRouter);

        $queryBuilder = $this->repository->createQueryBuilder('o');
        $ext->applyToCollection($queryBuilder, new QueryNameGenerator(), $this->entityClassName, context: ['filters' => ['id' => (string)$id]]);

        $result = $queryBuilder->getQuery()->getResult();
        $this->assertCount(1, $result);
        $this->assertSame($dummy->getId(), $result[0]->getId());
    }

    public function testFilterWithNumericIds(): void
    {
        DummyFactory::createMany(3);

        $dummy1 = DummyFactory::createOne();
        $dummy2 = DummyFactory::createOne();
        $id1 = $dummy1->getId();
        $id2 = $dummy2->getId();

        DummyFactory::assert()->count(5);

        $mockedRouter = $this->createMock(RouterInterface::class);
        $mockedRouter->expects($this->never())->method('match');

        $ext = new IdentifierCollectionFilterExtension($mockedRouter);

        $queryBuilder = $this->createQueryBuilder();
        $ext->applyToCollection($queryBuilder, new QueryNameGenerator(), $this->entityClassName, context: ['filters' => ['id' => [(string)$id1, (string)$id2]]]);

        $result = $queryBuilder->orderBy('o.id', 'asc')->getQuery()->getResult();
        $this->assertCount(2, $result);
        $this->assertSame($dummy1->getId(), $result[0]->getId());
        $this->assertSame($dummy2->getId(), $result[1]->getId());
    }


    public function testFilterThrowExceptionIfUseIriAndIdAtSameTime(): void
    {
        DummyFactory::createMany(3);

        $dummy1 = DummyFactory::createOne();
        $dummy2 = DummyFactory::createOne();
        $id = $dummy1->getId();
        $uuid = (string)$dummy2->getUuid();

        DummyFactory::assert()->count(5);

        $mockedRouter = $this->createMock(RouterInterface::class);
        $mockedRouter->expects($this->exactly(2))->method('match')->willReturn(
            ['_api_identifiers' => ['id'], 'id' => $id],
            ['_api_identifiers' => ['uuid'], 'uuid' => $uuid],
        );

        $ext = new IdentifierCollectionFilterExtension($mockedRouter);

        $queryBuilder = $this->createQueryBuilder();

        $this->expectException(\LogicException::class);
        $ext->applyToCollection($queryBuilder, new QueryNameGenerator(), $this->entityClassName, context: ['filters' => ['id' => ['/api/' . $id, '/api/' . $uuid]]]);
    }
}
