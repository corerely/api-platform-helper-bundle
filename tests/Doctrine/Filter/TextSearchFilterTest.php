<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Tests\Doctrine\Filter;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use Corerely\ApiPlatformHelperBundle\Doctrine\Filter\TextSearchFilter;
use Corerely\ApiPlatformHelperBundle\Tests\Doctrine\AbstractDoctrineExtensionTest;
use Corerely\ApiPlatformHelperBundle\Tests\Factory\DummyAssociationFactory;
use Corerely\ApiPlatformHelperBundle\Tests\Factory\DummyFactory;
use Corerely\ApiPlatformHelperBundle\Tests\Fixtures\Entity\Dummy;
use Doctrine\ORM\QueryBuilder;
use Zenstruck\Foundry\Proxy;

class TextSearchFilterTest extends AbstractDoctrineExtensionTest
{
    public function testFilterByProperty(): void
    {
        [$dummyExpectToFound] = $this->fixtures();

        $filters = [
            'q' => 'dummy',
        ];

        DummyFactory::assert()->count(5);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->managerRegistry->getManagerForClass(Dummy::class)->getRepository(Dummy::class)->createQueryBuilder('o');

        $filter = $this->createFilter();
        $filter->apply($queryBuilder, new QueryNameGenerator(), Dummy::class, null, ['filters' => $filters]);

        $result = $queryBuilder->getQuery()->getResult();

        self::assertCount(1, $result);
        self::assertSame($dummyExpectToFound->getId(), $result[0]->getId());
    }

    public function testFilterByAssociationProperty(): void
    {
        [, $dummyWithAssociationExpectToFound] = $this->fixtures();

        $filters = [
            'q' => 'lorem ipsum',
        ];

        DummyFactory::assert()->count(5);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->managerRegistry->getManagerForClass(Dummy::class)->getRepository(Dummy::class)->createQueryBuilder('o');

        $filter = $this->createFilter();
        $filter->apply($queryBuilder, new QueryNameGenerator(), Dummy::class, null, ['filters' => $filters]);

        $result = $queryBuilder->getQuery()->getResult();

        self::assertCount(1, $result);
        self::assertSame($dummyWithAssociationExpectToFound->getId(), $result[0]->getId());
    }

    private function createFilter(): TextSearchFilter
    {
        return new TextSearchFilter($this->managerRegistry, properties: ['name' => null, 'dummyAssociations.description' => null]);
    }

    /**
     * @return Proxy[]|Dummy[]
     */
    private function fixtures(): array
    {
        DummyFactory::new()->withAssociations()->many(3)->create();

        $dummy = DummyFactory::createOne(['name' => 'My dummy']);
        $dummyWithAssociation = DummyFactory::createOne([
            'dummyAssociations' => [
                DummyAssociationFactory::createOne(['description' => 'Association lorem ipsum description text']),
                DummyAssociationFactory::createOne(),
            ],
        ]);

        return [$dummy, $dummyWithAssociation];
    }
}
