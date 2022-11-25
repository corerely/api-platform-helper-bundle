<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Tests\Doctrine\Extension;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use Corerely\ApiPlatformHelperBundle\Doctrine\Extension\OrderByFieldsExtension;
use Corerely\ApiPlatformHelperBundle\Tests\Doctrine\AbstractDoctrineExtensionTest;
use Corerely\ApiPlatformHelperBundle\Tests\Factory\DummyFactory;
use Corerely\ApiPlatformHelperBundle\Tests\Fixtures\Entity\Dummy;
use Doctrine\ORM\QueryBuilder;

class OrderByFieldsExtensionTest extends AbstractDoctrineExtensionTest
{

    /**
     * @dataProvider ordersDataProvider
     */
    public function testApplyToCollectionWithSupportedField(string $order): void
    {
        $ext = new OrderByFieldsExtension('order', ['name']);

        $d1 = DummyFactory::createOne(['name' => 'aName']);
        $d2 = DummyFactory::createOne(['name' => 'dName']);
        $d3 = DummyFactory::createOne(['name' => 'cName']);
        $d4 = DummyFactory::createOne(['name' => 'bName']);

        DummyFactory::assert()->count(4);

        $queryBuilder = $this->repository->createQueryBuilder('o');
        $ext->applyToCollection($queryBuilder, new QueryNameGenerator(), $this->entityClassName, context: ['filters' => ['order' => ['name' => $order]]]);

        $result = $queryBuilder->getQuery()->getResult();
        $expect = $order === 'asc' ? [$d1, $d4, $d3, $d2] : [$d2, $d3, $d4, $d1];

        foreach ($expect as $key => $item) {
            self::assertSame($item->getId(), $result[$key]->getId());
        }
    }

    public function ordersDataProvider(): iterable
    {
        yield ['asc'];
        yield ['desc'];
    }

    public function testApplyToCollectionWithNotSupportedField(): void
    {
        $ext = new OrderByFieldsExtension('order', ['createdAt']);

        $mockedQB = $this->createMock(QueryBuilder::class);
        $mockedQB->expects($this->never())->method('getRootAliases');
        $mockedQB->expects($this->never())->method('orderBy');

        $ext->applyToCollection($mockedQB, new QueryNameGenerator(), Dummy::class, context: ['filters' => ['order' => ['updatedAt' => 'desc']]]);
    }

    public function testApplyToCollectionWithNotSupportedOrder(): void
    {
        $ext = new OrderByFieldsExtension('order', ['createdAt']);

        $mockedQB = $this->createMock(QueryBuilder::class);
        $mockedQB->expects($this->never())->method('getRootAliases');
        $mockedQB->expects($this->never())->method('orderBy');

        $ext->applyToCollection($mockedQB, new QueryNameGenerator(), Dummy::class, context: ['filters' => ['order' => ['createdAt' => 'ascc']]]);
    }
}
