<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Doctrine\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractContextAwareFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

final class TextSearchFilter extends AbstractContextAwareFilter
{

    public function __construct(ManagerRegistry $managerRegistry, ?RequestStack $requestStack = null, LoggerInterface $logger = null, array $properties = null, NameConverterInterface $nameConverter = null, private string $parameterName = 'q', private bool $caseSensitive = false)
    {
        parent::__construct($managerRegistry, $requestStack, $logger, $properties, $nameConverter);
    }

    protected function filterProperty(string $property, mixed $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null): void
    {

        // If filter property is not a "q"
        if ($this->parameterName !== $property) {
            return;
        }

        // Do nothing if search is empty
        $value = trim($value);
        if (empty($value)) {
            return;
        }

        $parameterName = $queryNameGenerator->generateParameterName($this->parameterName);
        $orX = $queryBuilder->expr()->orX();

        foreach ($this->properties as $property => $_) {
            $alias = $queryBuilder->getRootAliases()[0];
            $field = $property;

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
            $metadata = $this->getNestedMetadata($resourceClass, $associations ?? []);

            if ($metadata->hasField($field)) {
                $orX->add(
                    $queryBuilder->expr()->like(
                        $this->wrapCase(sprintf('%s.%s', $alias, $field)),
                        (string)$queryBuilder->expr()->concat("'%'", ':'.$parameterName, "'%'")
                    )
                );
            }
        }

        $queryBuilder
            ->andWhere($orX)
            ->setParameter($parameterName, $this->caseSensitive ? $value : strtolower($value));
    }

    public function getDescription(string $resourceClass): array
    {
        if (!$this->properties) {
            return [];
        }

        return [
            $this->parameterName => [
                'property' => implode(', ', array_keys($this->properties)),
                'type' => 'string',
                'required' => false,
                'swagger' => [
                    'description' => 'Selects entities where each search term is '.
                        'found somewhere in at least one of the specified properties',
                ],
            ],
        ];
    }

    private function wrapCase(string $alias): string
    {
        if ($this->caseSensitive) {
            return $alias;
        }

        return sprintf('LOWER(%s)', $alias);
    }
}
