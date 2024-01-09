<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Doctrine\Filter;

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\PropertyHelperTrait;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Exception\ItemNotFoundException;
use ApiPlatform\Metadata\Operation;
use Corerely\ApiPlatformHelperBundle\Doctrine\Common\FilterByIdsCommonTrait;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

final class UuidFilter extends AbstractFilter
{
    use PropertyHelperTrait;
    use FilterByIdsCommonTrait;

    public function __construct(
        private readonly IriConverterInterface $iriConverter,
        ManagerRegistry                        $managerRegistry,
        LoggerInterface                        $logger = null,
        ?array                                 $properties = null,
        ?NameConverterInterface                $nameConverter = null,
    ) {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);
    }

    public function getDescription(string $resourceClass): array
    {
        if (! $this->properties) {
            return [];
        }

        $description = [];
        foreach ($this->properties as $property => $strategy) {
            $description[$property] = [
                'property' => $property,
                'type' => 'string',
                'required' => false,
                'openapi' => [
                    'description' => 'Filter Uuid property.',
                    'name' => 'Uuid Search filter',
                ],
            ];
        }

        return $description;
    }

    protected function filterProperty(string $property, mixed $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
        if (null === $value ||
            ! $this->isPropertyEnabled($property, $resourceClass) ||
            ! $this->isPropertyMapped($property, $resourceClass, true)
        ) {
            return;
        }

        $values = $this->normalizeValue((array)$value);
        if (null === $values) {
            return;
        }

        $metadata = $this->getClassMetadata($resourceClass);
        if ($metadata->hasAssociation($property)) {
            $property .= '.uuid';
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $field = $property;
        $associations = [];
        if ($this->isPropertyNested($property, $resourceClass)) {
            [$alias, $field, $associations] = $this->addJoinsForNestedProperty($property, $alias, $queryBuilder, $queryNameGenerator, $resourceClass, Join::INNER_JOIN);
        }

        $metadata = $this->getNestedMetadata($resourceClass, $associations);
        if (! $metadata->hasField($field)) {
            return;
        }

        $uuids = array_filter(array_map($this->getUuidFromIri(...), $values));
        if (empty($uuids)) {
            return;
        }

        $this->andWhere($queryBuilder, $queryNameGenerator, $alias, $field, $uuids);
    }

    protected function getUuidFromIri(string $iri): string|null
    {
        try {
            $item = $this->iriConverter->getResourceFromIri($iri);
            if (! method_exists($item, 'getUuid')) {
                throw new \Exception('Item does not have getUuid method');
            }

            return $item->getUuid()->toBinary();
        } catch (InvalidArgumentException|ItemNotFoundException) {
            // ignore
        }

        return null;
    }
}
