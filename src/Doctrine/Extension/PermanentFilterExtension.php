<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Doctrine\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Corerely\ApiPlatformHelperBundle\Annotation\ApiPermanentFilter;
use Corerely\ApiPlatformHelperBundle\Doctrine\PermanentFilter\PermanentFilterInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class PermanentFilterExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{

    public function __construct(private readonly ServiceLocator $locator)
    {
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
        $this->apply($queryBuilder, $queryNameGenerator, $resourceClass, $operation, $context, null);
    }

    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, Operation $operation = null, array $context = []): void
    {
        $this->apply($queryBuilder, $queryNameGenerator, $resourceClass, $operation, $context, $identifiers);
    }

    private function getFilter(string $className): PermanentFilterInterface
    {
        if (!$this->locator->has($className)) {
            throw new \InvalidArgumentException(sprintf('Permanent filter "%s" was not found. Did you forget to implement interface "%s"', $className, PermanentFilterInterface::class));
        }

        return $this->locator->get($className);
    }

    private function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?string $operationName, array $context, ?array $identifiers): void
    {
        $attributes = (new \ReflectionClass($resourceClass))->getAttributes(ApiPermanentFilter::class);

        foreach ($attributes as $attribute) {
            /** @var ApiPermanentFilter $attributeInstance */
            $attributeInstance = $attribute->newInstance();

            $filter = $this->getFilter($attributeInstance->filterClassName);
            $filter->apply($queryBuilder, $queryNameGenerator, $resourceClass, $operationName, $context, $attributeInstance->options, $identifiers);
        }
    }
}
