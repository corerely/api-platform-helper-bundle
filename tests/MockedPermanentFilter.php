<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Tests;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Corerely\ApiPlatformHelperBundle\Doctrine\PermanentFilter\PermanentFilterInterface;
use Doctrine\ORM\QueryBuilder;

class MockedPermanentFilter implements PermanentFilterInterface
{
    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null, array $context = [], array $options = [], array $identifiers = []): void
    {
    }
}
