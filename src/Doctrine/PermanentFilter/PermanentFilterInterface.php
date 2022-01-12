<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Doctrine\PermanentFilter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;

interface PermanentFilterInterface
{
    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null, array $context = [], array $options = [], array $identifiers = []): void;
}
