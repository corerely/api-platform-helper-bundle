<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Doctrine\Common;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;

trait FilterByIdsCommonTrait
{
    private function normalizeValue(mixed $value): ?array
    {
        if (null === $value) {
            return null;
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        // Supports only IRIs
        $value = array_filter($value, static fn(mixed $val) => is_string($val) && '' !== $val);

        if (empty($value)) {
            return null;
        }

        return array_values($value);
    }

    private function andWhere(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $alias, string $fieldName, array $ids): void
    {
        $fieldName = sprintf('%s.%s', $alias, $fieldName);
        $parameterName = ':' . $queryNameGenerator->generateParameterName($fieldName);

        if (count($ids) > 1) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->in($fieldName, $parameterName))
                ->setParameter($parameterName, $ids);

            return;
        }

        $queryBuilder
            ->andWhere($queryBuilder->expr()->eq($fieldName, $parameterName))
            ->setParameter($parameterName, $ids[0]);
    }
}
