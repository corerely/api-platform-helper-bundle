<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Tests\Doctrine\Filter;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use Corerely\ApiPlatformHelperBundle\Doctrine\Filter\TextSearchFilter;
use Corerely\ApiPlatformHelperBundle\Tests\Doctrine\AbstractDoctrineExtension;
use Corerely\ApiPlatformHelperBundle\Tests\Factory\DummyAssociationFactory;
use Corerely\ApiPlatformHelperBundle\Tests\Factory\DummyFactory;
use Corerely\ApiPlatformHelperBundle\Tests\Fixtures\Entity\Dummy;
use Doctrine\ORM\QueryBuilder;

class TextSearchFilterTest extends AbstractDoctrineExtension
{
    public function testFilterByPropertyPartial(): void
    {
        [$dummyExpectToFound] = $this->fixtures();

        $filters = [
            'q' => 'name of',
        ];

        DummyFactory::assert()->count(5);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->managerRegistry->getManagerForClass(Dummy::class)->getRepository(Dummy::class)->createQueryBuilder('o');

        $filter = new TextSearchFilter($this->managerRegistry, properties: ['name' => null]); // Null default strategy is partial
        $filter->apply($queryBuilder, new QueryNameGenerator(), Dummy::class, null, ['filters' => $filters]);

        $result = $queryBuilder->getQuery()->getResult();

        self::assertCount(1, $result);
        self::assertSame($dummyExpectToFound->getId(), $result[0]->getId());
    }

    public function testFilterByPropertyStart(): void
    {
        [$dummyExpectToFound] = $this->fixtures();

        $filters = [
            'q' => 'start',
        ];

        DummyFactory::assert()->count(5);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->managerRegistry->getManagerForClass(Dummy::class)->getRepository(Dummy::class)->createQueryBuilder('o');

        $filter = new TextSearchFilter($this->managerRegistry, properties: ['name' => TextSearchFilter::SEARCH_START]);
        $filter->apply($queryBuilder, new QueryNameGenerator(), Dummy::class, null, ['filters' => $filters]);

        $result = $queryBuilder->getQuery()->getResult();

        self::assertCount(1, $result);
        self::assertSame($dummyExpectToFound->getId(), $result[0]->getId());
    }

    public function testFilterByPropertyEnd(): void
    {
        [$dummyExpectToFound] = $this->fixtures();

        $filters = [
            'q' => 'dummy',
        ];

        DummyFactory::assert()->count(5);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->managerRegistry->getManagerForClass(Dummy::class)->getRepository(Dummy::class)->createQueryBuilder('o');

        $filter = new TextSearchFilter($this->managerRegistry, properties: ['name' => TextSearchFilter::SEARCH_END]);
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

        $filter = new TextSearchFilter($this->managerRegistry, properties: ['dummyAssociations.description' => null]);
        $filter->apply($queryBuilder, new QueryNameGenerator(), Dummy::class, null, ['filters' => $filters]);

        $result = $queryBuilder->getQuery()->getResult();

        self::assertCount(1, $result);
        self::assertSame($dummyWithAssociationExpectToFound->getId(), $result[0]->getId());
    }

    /**
     * @return Dummy[]|\Zenstruck\Foundry\Proxy[]|\Zenstruck\Foundry\Persistence\Proxy[]
     */
    private function fixtures(): array
    {
        DummyFactory::new()->withAssociations()->many(3)->create();

        $dummy = DummyFactory::createOne(['name' => 'Start name of dummy']);
        $dummyWithAssociation = DummyFactory::createOne([
            'dummyAssociations' => [
                DummyAssociationFactory::createOne(['description' => 'Association lorem ipsum description text']),
                DummyAssociationFactory::createOne(),
            ],
        ]);

        return [$dummy, $dummyWithAssociation];
    }
}
