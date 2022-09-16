<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Doctrine\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Corerely\ApiPlatformHelperBundle\Doctrine\Common\FilterByIdsCommonTrait;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\RouterInterface;

final class IdentifierCollectionFilterExtension implements QueryCollectionExtensionInterface
{
    use FilterByIdsCommonTrait;

    /**
     * Identifier possible fields
     */
    private const IDENTIFIER_FIELD_ID = 'id';
    private const IDENTIFIER_FIELD_UUID = 'uuid';

    private string $identifierFieldName;

    public function __construct(private RouterInterface $router)
    {
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
        $value = $this->normalizeValue($context['filters']['id'] ?? null);
        if (null === $value) {
            return;
        }

        $ids = $this->getIdsAndDetectIdentifierField($value, $resourceClass);
        if (empty($ids)) {
            return;
        }

        if (self::IDENTIFIER_FIELD_UUID === $this->identifierFieldName) {
            $ids = $this->uuidsToBinary($ids);
        }

        $this->andWhere($queryBuilder, $queryNameGenerator, $queryBuilder->getRootAliases()[0], $this->identifierFieldName, $ids);
    }

    private function getIdsAndDetectIdentifierField(array $items, string $resourceClass): array
    {
        $ids = [];

        foreach ($items as $item) {
            // If filter item is numeric assume this is an array of IDs and not IRIs
            if (is_numeric($item)) {
                $this->saveIdentifierFieldName(self::IDENTIFIER_FIELD_ID);
                $ids[] = (int)$item;

                continue;
            }

            // Otherwise, assume we have IRI item, try to resolve ID from IRI
            try {
                $parameters = $this->router->match($item);
                $identifiers = $parameters['_api_identifiers'] ?? null;

                if (!is_array($identifiers)) {
                    continue;
                }
                if (count($identifiers) !== 1) {
                    throw new \LogicException(sprintf('Expect that "%s" has exactly one identifier, %d found.', $resourceClass, count($identifiers)));
                }

                $identifier = array_shift($identifiers);

                if (!in_array($identifier, [self::IDENTIFIER_FIELD_ID, self::IDENTIFIER_FIELD_UUID], true)) {
                    throw new \LogicException(sprintf('Unsupported identifier "%s"', $identifier));
                }

                $id = $parameters[$identifier] ?? null;
                if (!$id) {
                    continue;
                }

                $this->saveIdentifierFieldName($identifier);
                $ids[] = $id;
            } catch (ExceptionInterface) {
            }
        }

        return $ids;
    }

    private function saveIdentifierFieldName(string $identifierFieldName): void
    {
        if (isset($this->identifierFieldName) && $identifierFieldName !== $this->identifierFieldName) {
            throw new \LogicException(sprintf('Identifier field can\'t change once it was set. Tried to changed from "%s" to "%s"', $this->identifierFieldName, $identifierFieldName));
        }

        $this->identifierFieldName = $identifierFieldName;
    }
}
