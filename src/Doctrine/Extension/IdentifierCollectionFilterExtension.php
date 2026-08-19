<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Doctrine\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Exception\ItemNotFoundException;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use Corerely\ApiPlatformHelperBundle\Doctrine\Common\FilterByIdsCommonTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\QueryBuilder;

final readonly class IdentifierCollectionFilterExtension implements QueryCollectionExtensionInterface
{
    use FilterByIdsCommonTrait;

    public function __construct(
        private IriConverterInterface $iriConverter,
    ) {
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $value = $this->normalizeValue($context['filters']['id'] ?? null);
        if (!$value) {
            return;
        }

        $ids = array_filter(array_map(fn(mixed $v) => $this->getIdFromIri($queryBuilder->getEntityManager(), $v), $value));
        if (!$ids) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $property = 'id';

        $this->andWhere($queryBuilder, $queryNameGenerator, $alias, $property, $ids);
    }

    /**
     * Gets the ID from an IRI or a raw ID.
     */
    protected function getIdFromIri(EntityManagerInterface $em, string $iri): int|string|null
    {
        if (is_numeric($iri)) {
            return (int) $iri;
        }

        try {
            $item = $this->iriConverter->getResourceFromIri($iri, ['fetch_data' => false]);
        } catch (InvalidArgumentException|ItemNotFoundException|EntityNotFoundException) {
            return null;
        }

        try {
            $manager = $em->getClassMetadata($item::class);
            $identifiers = $manager->getIdentifierValues($item);

            return [] === $identifiers ? null : reset($identifiers);
        } catch (\Doctrine\Persistence\Mapping\MappingException) {
        }

        // Non-Doctrine resource (e.g. a mapped DTO): read its identifier directly.
        if (method_exists($item, 'getId')) {
            return $item->getId();
        }

        $reflection = new \ReflectionObject($item);
        return $reflection->hasProperty('id') ? (int) $reflection->getProperty('id')->getValue($item) : null;
    }
}
