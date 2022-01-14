<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Tests\Doctrine\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use Corerely\ApiPlatformHelperBundle\Doctrine\Extension\OrderByFieldsExtension;
use Corerely\ApiPlatformHelperBundle\Tests\Fixtures\Entity\Dummy;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

class OrderByFieldsExtensionTest extends TestCase
{

    /**
     * @dataProvider ordersDataProvider
     */
    public function testApplyToCollectionWithSupportedField(string $order): void
    {
        $ext = new OrderByFieldsExtension('order', ['createdAt']);

        $mockedQB = $this->createMock(QueryBuilder::class);
        $mockedQB->expects($this->once())->method('getRootAliases')->willReturn(['o']);
        $mockedQB->expects($this->once())->method('orderBy')->with('o.createdAt', strtoupper($order));

        $ext->applyToCollection($mockedQB, new QueryNameGenerator(), Dummy::class, context: ['filters' => ['order' => ['createdAt' => $order]]]);
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
