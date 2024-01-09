<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Doctrine\Extension;

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Exception\ItemNotFoundException;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Operation;
use Corerely\ApiPlatformHelperBundle\Doctrine\Common\FilterByIdsCommonTrait;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Uid\Uuid;

final class IdentifierCollectionFilterExtension implements QueryCollectionExtensionInterface
{
    use FilterByIdsCommonTrait;

    public function __construct(
        private readonly IriConverterInterface $iriConverter,
    ) {
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
        $value = $this->normalizeValue($context['filters']['id'] ?? null);
        if (null === $value) {
            return;
        }


        [$property, $normalizer] = $this->isUuidMode($resourceClass)
            ? ['uuid', $this->getUuidFromIri(...)]
            : ['id', $this->getIdFromIri(...)];
        $ids = array_filter(array_map($normalizer, $value));

        if (! $ids) {
            return;
        }

        $this->andWhere($queryBuilder, $queryNameGenerator, $queryBuilder->getRootAliases()[0], $property, $ids);
    }

    private function getUuidFromIri(string $iri): string|null
    {
        try {
            $item = $this->iriConverter->getResourceFromIri($iri);

            return $item->getUuid()->toBinary();
        } catch (InvalidArgumentException|ItemNotFoundException) {
        }

        return null;
    }

    /**
     * Gets the ID from an IRI or a raw ID.
     */
    protected function getIdFromIri(string $iri): int|string|null
    {
        try {
            $item = $this->iriConverter->getResourceFromIri($iri, ['fetch_data' => false]);

            return $item->getId();
        } catch (InvalidArgumentException|ItemNotFoundException) {
        }

        return null;
    }

    private function isUuidMode(string $resourceClass): bool
    {
        if (! class_exists(Uuid::class)) {
            return false;
        }

        $reflectionClass = new \ReflectionClass($resourceClass);
        if (! $reflectionClass->hasProperty('uuid')) {
            return false;
        }

        $reflectionProperty = $reflectionClass->getProperty('uuid');

        return ($reflectionProperty->getAttributes(ApiProperty::class)[0] ?? null)?->newInstance()->isIdentifier() === true;
    }
}
