<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Doctrine\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

final class TextSearchFilter extends AbstractFilter
{

    public const string SEARCH_START = 'start';
    public const string SEARCH_END = 'end';
    public const string SEARCH_PARTIAL = 'partial';

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
        $ors = [];

        foreach ($this->properties as $property => $strategy) {
            $strategy ??= self::SEARCH_PARTIAL;
            $alias = $queryBuilder->getRootAliases()[0];
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

                $ors[] = match ($strategy) {
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
            }
        }

        if (! $ors) {
            return;
        }

        $queryBuilder
            ->andWhere($queryBuilder->expr()->orX(...$ors))
            ->setParameter($parameterName, $this->caseSensitive ? $value : strtolower($value))
        ;
    }

    public function getDescription(string $resourceClass): array
    {
        if (! $this->properties) {
            return [];
        }

        return [
            $this->parameterName => [
                'property' => $this->parameterName,
                'type' => 'string',
                'required' => false,
                'description' => 'Search in string properties: '.implode(', ', array_map(static fn(string $property): string => '"'.$property.'"', array_keys($this->properties))),
            ],
        ];
    }
}
