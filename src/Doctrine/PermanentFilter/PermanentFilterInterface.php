<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Doctrine\PermanentFilter;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

interface PermanentFilterInterface
{
    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = [], array $options = [], array $identifiers = null): void;
}
