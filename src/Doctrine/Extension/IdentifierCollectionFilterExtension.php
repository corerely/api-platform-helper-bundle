<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Doctrine\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\ContextAwareQueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Corerely\ApiPlatformHelperBundle\Doctrine\Common\FilterByIdsCommonTrait;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\RouterInterface;

final class IdentifierCollectionFilterExtension implements ContextAwareQueryCollectionExtensionInterface
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

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null, array $context = []): void
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

                $this->identifierFieldName = $identifier;
                $ids[] = $id;
            } catch (ExceptionInterface) {
            }
        }

        return $ids;
    }
}
