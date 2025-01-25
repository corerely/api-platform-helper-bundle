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
use Corerely\ApiPlatformHelperBundle\Doctrine\IdentifierMode;
use Doctrine\ORM\QueryBuilder;

final readonly class IdentifierCollectionFilterExtension implements QueryCollectionExtensionInterface
{
    use FilterByIdsCommonTrait;

    public function __construct(
        private IriConverterInterface $iriConverter,
        private IdentifierMode        $identifierMode,
    ) {
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
        $value = $this->normalizeValue($context['filters']['id'] ?? null);
        if (! $value) {
            return;
        }

        $ids = array_filter(array_map($this->getIdFromIri(...), $value));
        if (! $ids) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $property = $this->identifierMode->identifierColumnName();

        $this->andWhere($queryBuilder, $queryNameGenerator, $alias, $property, $ids);
    }

    /**
     * Gets the ID from an IRI or a raw ID.
     */
    protected function getIdFromIri(string $iri): int|string|null
    {
        if (is_numeric($iri) && $this->identifierMode === IdentifierMode::ID) {
            return (int) $iri;
        }

        try {
            $item = $this->iriConverter->getResourceFromIri($iri, ['fetch_data' => false]);

            return match ($this->identifierMode) {
                IdentifierMode::ID => $item->getId(),
                IdentifierMode::UUID => $item->getUuid()->toBinary(),
            };
        } catch (InvalidArgumentException|ItemNotFoundException) {
            return null;
        }
    }
}
