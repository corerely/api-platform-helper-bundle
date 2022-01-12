<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Doctrine\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractContextAwareFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

final class TextSearchFilter extends AbstractContextAwareFilter
{
    private string $paramName = 'q';

    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null): void
    {
        // If filter property is not a "q"
        if ($this->paramName !== $property) {
            return;
        }

        // Do nothing if search is empty
        $value = trim($value);
        if (empty($value)) {
            return;
        }

        $parameterName = $queryNameGenerator->generateParameterName($this->paramName);
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
                        sprintf('%s.%s', $alias, $field),
                        (string)$queryBuilder->expr()->concat("'%'", ':' . $parameterName, "'%'")
                    )
                );
            }
        }

        $queryBuilder
            ->andWhere($orX)
            ->setParameter($parameterName, $value);
    }

    public function getDescription(string $resourceClass): array
    {
        if (! $this->properties) {
            return [];
        }

        return [
            $this->paramName => [
                'property' => implode(', ', array_keys($this->properties)),
                'type' => 'string',
                'required' => false,
                'swagger' => [
                    'description' => 'Selects entities where each search term is ' .
                        'found somewhere in at least one of the specified properties',
                ],
            ],
        ];
    }
}
