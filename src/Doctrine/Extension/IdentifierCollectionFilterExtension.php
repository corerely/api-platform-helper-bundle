<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Doctrine\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\ContextAwareQueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Uid\Uuid;

final class IdentifierCollectionFilterExtension implements ContextAwareQueryCollectionExtensionInterface
{
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
        $value = $this->normalizeValue($context);
        if (null === $value) {
            return;
        }

        $ids = $this->getIdsAndDetectIdentifierField($value, $resourceClass);
        if (empty($ids)) {
            return;
        }

        $this->andWhere($queryBuilder, $queryNameGenerator, $ids);
    }

    private function andWhere(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, array $ids): void
    {
        if (self::IDENTIFIER_FIELD_UUID === $this->identifierFieldName) {
            $ids = array_map(static fn(string $uuid) => Uuid::fromString($uuid)->toBinary(), $ids);
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $fieldName = sprintf('%s.%s', $alias, $this->identifierFieldName);
        $parameterName = ':' . $queryNameGenerator->generateParameterName($this->identifierFieldName);

        if (count($ids) > 1) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->in($fieldName, $parameterName))
                ->setParameter($parameterName, $ids);

            return;
        }

        $queryBuilder
            ->andWhere($queryBuilder->expr()->eq($fieldName, $parameterName))
            ->setParameter($parameterName, $ids[0]);
    }

    private function normalizeValue(array $context): ?array
    {
        $value = $context['filters']['id'] ?? null;

        if (null === $value) {
            return null;
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        // Supports only IRI
        $value = array_filter($value, static fn(mixed $val) => is_string($val) && '' !== $val);

        if (empty($value)) {
            return null;
        }

        return array_values($value);
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
