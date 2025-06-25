<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Doctrine\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\PropertyInfo\Type;

class TextSearchFilter extends AbstractFilter
{

    public const SEARCH_START = 'start';
    public const SEARCH_END = 'end';
    public const SEARCH_PARTIAL = 'partial';

    public function __construct(
        ManagerRegistry         $managerRegistry,
        ?LoggerInterface        $logger = null,
        ?array                  $properties = null,
        ?NameConverterInterface $nameConverter = null,
        private readonly string $parameterName = 'q',
        private readonly bool   $caseSensitive = false,
    ) {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);
    }

    protected function filterProperty(string $property, mixed $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {

        // If filter property is not a "q"
        if ($this->parameterName !== $property) {
            return;
        }

        // Do nothing if search is empty
        $value = trim((string) $value);
        if (empty($value)) {
            return;
        }

        $parameterName = $queryNameGenerator->generateParameterName($this->parameterName);
        $orX = $queryBuilder->expr()->orX();

        foreach ($this->properties as $property => $strategy) {
            $this->applyPropertySearch($value, $property, $strategy, $queryBuilder, $queryNameGenerator, $orX, $resourceClass);
        }

        if ($orX->count() > 0) {
            $queryBuilder->andWhere($orX);
        }
    }

    public function getDescription(string $resourceClass): array
    {
        if (! $this->properties) {
            return [];
        }

        return [
            $this->parameterName => [
                'property' => $this->parameterName,
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'description' => 'Search in string properties: '.implode(', ', array_map(static fn(string $property): string => '"'.$property.'"', array_keys($this->properties))),
            ],
        ];
    }

    protected function applyPropertySearch(
        string                      $value,
        string                      $property,
        ?string                     $strategy,
        QueryBuilder                $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        Orx                         $orX,
        string                      $resourceClass,
    ): void {
        $strategy ??= self::SEARCH_PARTIAL;

        $alias = $queryBuilder->getRootAliases()[0];
        $parameterName = $queryNameGenerator->generateParameterName($property);
        $field = $property;

        $associations = [];
        if ($this->isPropertyNested($property, $resourceClass)) {
            [$alias, $field, $associations] = $this->addJoinsForNestedProperty(
                $property,
                $alias,
                $queryBuilder,
                $queryNameGenerator,
                $resourceClass,
                Join::LEFT_JOIN,
            );
        }

        $metadata = $this->getNestedMetadata($resourceClass, $associations);

        if ($metadata->hasField($field)) {
            $aliasedField = sprintf('%s.%s', $alias, $field);

            if (! $this->caseSensitive) {
                $aliasedField = sprintf('LOWER(%s)', $aliasedField);
            }

            $or = match ($strategy) {
                self::SEARCH_END => $queryBuilder->expr()->like(
                    $aliasedField,
                    (string) $queryBuilder->expr()->concat("'%'", ':'.$parameterName),
                ),
                self::SEARCH_START => $queryBuilder->expr()->like(
                    $aliasedField,
                    (string) $queryBuilder->expr()->concat(':'.$parameterName, "'%'"),
                ),
                default => $queryBuilder->expr()->like(
                    $aliasedField,
                    (string) $queryBuilder->expr()->concat("'%'", ':'.$parameterName, "'%'"),
                ),
            };

            $orX->add($or);
            $queryBuilder->setParameter($parameterName, $this->caseSensitive ? $value : strtolower($value));
        }
    }
}
