<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Test;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

abstract class ApiTestCase extends \ApiPlatform\Symfony\Bundle\Test\ApiTestCase
{
    use ResetDatabase, Factories;
    use FactoriesProxyHelper;

    private PropertyAccessorInterface $propertyAccessor;

    protected function createClientAdapter(): ClientAdapter
    {
        return new ClientAdapter(self::createClient(), $this->getUserManager(), $this->getClientAuthenticator());
    }

    protected function addQueryParamsToUrl(string $url, array $params): string
    {
        $query = http_build_query($params);

        return sprintf('%s%s%s', $url, str_contains($url, '?') ? '&' : '?', $query);
    }

    protected function getItemsIriIdsArray(iterable $collection): array
    {
        $result = [];
        foreach ($collection as $item) {
            $result[] = $this->getItemIriIdArray($item);
        }

        return $result;
    }

    protected function getItemIriIdArray(object $entity): array
    {
        return ['@id' => $this->getItemIri($entity)];
    }

    protected function getItemIri(object $entity): string
    {
        return self::getContainer()->get('api_platform.iri_converter')->getIriFromResource(
            $this->getRealEntityObject($entity),
        );
    }

    // Assert helpers
    protected function assertResponseIsCreated(): void
    {
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    protected function assertResponseIsForbidden(): void
    {
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    protected function assertResponseIsUnprocessableEntity(): void
    {
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    protected function assertResponseIsNoContent(): void
    {
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    protected function serializeData(array $data): array
    {
        return array_map(fn(mixed $val) => $this->serializeValue($val), $data);
    }

    protected function serializeEntity(object $entity, array $properties): array
    {
        $serialized = [
            '@id' => $this->getItemIri($entity),
        ];
        $serialized += $this->serializeCommonFields($entity);

        $entity = $this->getRealEntityObject($entity);

        $propertyAccessor = $this->getPropertyAccessor();

        foreach ($properties as $key => $property) {
            // If property is array, then need to serialize embedded entity
            if (is_array($property)) {
                $embeddedProperties = $property;
                $property = $key;

                $value = $propertyAccessor->getValue($entity, $property);

                if (is_iterable($value)) {
                    $serialized[$property] = array_map(fn(object $item) => $this->serializeEntity($item, $embeddedProperties), [...$value]);
                } elseif (null !== $value) {
                    $serialized[$property] = $this->serializeEntity($value, $embeddedProperties);
                }

                continue;
            }

            $serialized[$property] = $this->serializeValue(
                $propertyAccessor->getValue($entity, $property),
            );
        }

        return $serialized;
    }

    private function serializeCommonFields(object $entity): array
    {
        $serialized = [];
        $entity = $this->getRealEntityObject($entity);

        if (method_exists($entity, 'getUuid')) {
            $serialized['uuid'] = (string)$entity->getUuid();
        }

        if (method_exists($entity, 'getPosition')) {
            $serialized['position'] = $entity->getPosition();
        }

        if (method_exists($entity, 'getCreatedAt')) {
            $serialized['createdAt'] = $this->serializeDateTimeAsString($entity->getCreatedAt());
        }
        if (method_exists($entity, 'getUpdatedAt')) {
            $serialized['updatedAt'] = $this->serializeDateTimeAsString($entity->getUpdatedAt());
        }

        return $serialized;
    }

    protected function serializeValue(mixed $value): mixed
    {
        return match (true) {
            $value instanceof \DateTimeInterface => $this->serializeDateTimeAsString($value),
            $value instanceof Collection => array_map(fn(object $item) => $this->getItemIri($item), $value->toArray()),
            is_object($value) && ($this->isEntityProxy($value) || str_contains(ClassUtils::getClass($value), 'App\\Entity\\')) => $this->getItemIri($value),
            $value instanceof \Stringable => (string)$value,
            $value instanceof \BackedEnum => $value->value,
            is_float($value) => $this->preciseZeroFraction($value),
            is_array($value) => array_map($this->serializeValue(...), $value),
            default => $value
        };
    }

    protected function preciseZeroFraction(float $value): int|float
    {
        if ($value === (float)(int)$value) {
            return (int)$value;
        }

        return $value;
    }

    protected function serializeDateTimeAsString(?\DateTimeInterface $dateTime): ?string
    {
        if (null === $dateTime) {
            return null;
        }

        return $dateTime->format(\DateTimeInterface::RFC3339);
    }

    protected function getManager(): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine')->getManager();
    }

    protected function getClientAuthenticator(): ClientAuthenticatorInterface
    {
        return new DefaultClientAuthenticator();
    }

    protected function getPropertyAccessor(): PropertyAccessorInterface
    {
        return $this->propertyAccessor ??= PropertyAccess::createPropertyAccessor();
    }

    abstract protected function getUserManager(): UserManagerInterface;
}
