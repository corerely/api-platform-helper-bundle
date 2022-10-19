<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Doctrine\Extension;

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Operation;
use Corerely\ApiPlatformHelperBundle\Doctrine\Common\FilterByIdsCommonTrait;
use Doctrine\ORM\QueryBuilder;

final class IdentifierCollectionFilterExtension implements QueryCollectionExtensionInterface
{
    use FilterByIdsCommonTrait;

    public function __construct(private readonly IriConverterInterface $iriConverter)
    {
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
        $value = $this->normalizeValue($context['filters']['id'] ?? null);
        if (null === $value) {
            return;
        }

        $ids = [];
        foreach ($value as $item) {
            // @TODO add support of uuid
            if ($id = $this->getIdFromValue($item)) {
                $ids[] = $id;
            }
        }

        if (!$ids) {
            return;
        }


        $this->andWhere($queryBuilder, $queryNameGenerator, $queryBuilder->getRootAliases()[0], 'id', $ids);
    }

    /**
     * Gets the ID from an IRI or a raw ID.
     */
    protected function getIdFromValue(string $value): int|string|null
    {
        try {
            $item = $this->iriConverter->getResourceFromIri($value, ['fetch_data' => false]);

            return $item->getId();
        } catch (InvalidArgumentException) {
            // Do nothing, return the raw value
        }

        return null;
    }
}
