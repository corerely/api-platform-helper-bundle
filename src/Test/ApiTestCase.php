<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Test;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

abstract class ApiTestCase extends \ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase
{
    use ResetDatabase, Factories;

    protected function createClientAdapter(): ClientAdapter
    {
        return new ClientAdapter(self::createClient(), $this->getUserManager());
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
        if ($entity instanceof Proxy) {
            $entity = $entity->object();
        }

        return self::getContainer()->get('api_platform.iri_converter')->getIriFromItem($entity);
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
            'uuid' => (string)$entity->getUuid(),
        ];
        $serialized += $this->serializeCommonFields($entity);

        if ($entity instanceof Proxy) {
            $entity = $entity->object();
        }

        foreach ($properties as $property) {
            $getter = match (true) {
                method_exists($entity, 'get' . ucfirst($property)) => 'get' . ucfirst($property),
                method_exists($entity, 'is' . ucfirst($property)) => 'is' . ucfirst($property),
                default => throw new \Exception(sprintf('Could not get property "%s"', $property))
            };

            $serialized[$property] = $this->serializeValue($entity->$getter());
        }

        return $serialized;
    }

    private function serializeCommonFields(object $entity): array
    {
        $serialized = [];
        if ($entity instanceof Proxy) {
            $entity = $entity->object();
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
            $value instanceof Proxy, is_object($value) && str_contains(ClassUtils::getClass($value), 'App\\Entity\\') => $this->getItemIri($value),
            $value instanceof \Stringable => (string)$value,
            $value instanceof \BackedEnum => $value->value,
            is_float($value) => $this->preciseZeroFraction($value),
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

    abstract protected function getUserManager(): UserManagerInterface;
}
