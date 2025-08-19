<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Doctrine\Extension;

use ApiPlatform\Doctrine\Common\Filter\OrderFilterInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

final readonly class OrderByFieldsExtension implements QueryCollectionExtensionInterface
{

    /**
     * @var string $paramName ['order' => ['createdAt' => 'desc']]
     * @var array $fields ['createdAt', 'updatedAt']
     */
    public function __construct(
        private string $paramName,
        private array  $fields,
    ) {
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $filterValue = $context['filters'][$this->paramName] ?? null;
        $intersect = array_values(
            array_intersect($this->fields, is_array($filterValue) ? array_keys($filterValue) : []),
        );

        // If exactly one order field is provided, and it's allowed by default order fields - apply filter
        if (count($intersect) !== 1) {
            return;
        }

        $property = $intersect[0];
        $direction = $this->normalizeDirection($filterValue[$property] ?? null);

        if (null === $direction) {
            return;
        }

        $this->addOrderBy($queryBuilder, $property, $direction);
    }

    private function addOrderBy(QueryBuilder $queryBuilder, string $property, string $direction): void
    {
        $alias = $queryBuilder->getRootAliases()[0];

        $queryBuilder->orderBy(sprintf('%s.%s', $alias, $property), $direction);
    }

    private function normalizeDirection(mixed $direction): ?string
    {
        if (! is_string($direction)) {
            return null;
        }

        $direction = strtoupper($direction);
        if (! in_array($direction, [OrderFilterInterface::DIRECTION_ASC, OrderFilterInterface::DIRECTION_DESC], true)) {
            return null;
        }

        return $direction;
    }
}
