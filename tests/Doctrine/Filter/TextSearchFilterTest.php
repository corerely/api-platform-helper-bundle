<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Tests\Doctrine\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use Corerely\ApiPlatformHelperBundle\Doctrine\AbstractDoctrineExtensionTest;
use Corerely\ApiPlatformHelperBundle\Doctrine\Filter\TextSearchFilter;
use Corerely\ApiPlatformHelperBundle\Tests\Factory\DummyAssociationFactory;
use Corerely\ApiPlatformHelperBundle\Tests\Factory\DummyFactory;
use Corerely\ApiPlatformHelperBundle\Tests\Fixtures\Entity\Dummy;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
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

        $filter = $this->createFilter($filters);
        $filter->apply($queryBuilder, new QueryNameGenerator(), Dummy::class, 'op', ['filters' => $filters]);

        $result = $queryBuilder->getQuery()->getResult();

        $this->assertCount(1, $result);
        $this->assertSame($dummyExpectToFound->getId(), $result[0]->getId());
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

        $filter = $this->createFilter($filters);
        $filter->apply($queryBuilder, new QueryNameGenerator(), Dummy::class, 'op', ['filters' => $filters]);

        $result = $queryBuilder->getQuery()->getResult();

        $this->assertCount(1, $result);
        $this->assertSame($dummyWithAssociationExpectToFound->getId(), $result[0]->getId());
    }

    private function createFilter(array $filters): TextSearchFilter
    {
        $requestStack = new RequestStack();
        $requestStack->push(Request::create('/api/dummies', 'GET', $filters));

        return new TextSearchFilter($this->managerRegistry, $requestStack, properties: ['name' => null, 'dummyAssociations.description' => null]);
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
