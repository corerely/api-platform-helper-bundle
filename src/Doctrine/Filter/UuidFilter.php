<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Doctrine\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractContextAwareFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\PropertyHelperTrait;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Corerely\ApiPlatformHelperBundle\Doctrine\Common\FilterByIdsCommonTrait;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

final class UuidFilter extends AbstractContextAwareFilter
{
    use PropertyHelperTrait;
    use FilterByIdsCommonTrait;

    public function __construct(private RouterInterface $router, ManagerRegistry $managerRegistry, ?RequestStack $requestStack = null, LoggerInterface $logger = null, array $properties = null, NameConverterInterface $nameConverter = null)
    {
        parent::__construct($managerRegistry, $requestStack, $logger, $properties, $nameConverter);
    }

    public function getDescription(string $resourceClass): array
    {
        if (!$this->properties) {
            return [];
        }

        $description = [];
        foreach ($this->properties as $property => $strategy) {
            $description[$property] = [
                'property' => $property,
                'type' => 'string',
                'required' => false,
                'swagger' => [
                    'description' => 'Filter Uuid property.',
                    'name' => 'Uuid Search filter',
                ],
            ];
        }

        return $description;
    }

    protected function filterProperty(string $property, mixed $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null): void
    {
        if (null === $value ||
            !$this->isPropertyEnabled($property, $resourceClass) ||
            !$this->isPropertyMapped($property, $resourceClass, true)
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
            [$alias, $field, $associations] = $this->addJoinsForNestedProperty($property, $alias, $queryBuilder, $queryNameGenerator, $resourceClass);
        }

        $metadata = $this->getNestedMetadata($resourceClass, $associations);
        if (!$metadata->hasField($field)) {
            return;
        }

        $uuids = array_map([$this, 'getUuidFromIri'], $values);
        $uuids = $this->uuidsToBinary($uuids);

        $this->andWhere($queryBuilder, $queryNameGenerator, $alias, $field, $uuids);
    }

    protected function getUuidFromIri(string $iri): string
    {
        try {
            $parameters = $this->router->match($iri);
            $identifiers = $parameters['_api_identifiers'] ?? null;

            if (is_array($identifiers)
                && in_array('uuid', $identifiers, true)
                && ($uuid = $parameters['uuid'] ?? null)
            ) {
                return $uuid;
            }
        } catch (ExceptionInterface) {
            // ignore
        }

        return $iri;
    }
}
