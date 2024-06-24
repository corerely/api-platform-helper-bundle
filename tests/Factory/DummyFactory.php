<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Tests\Factory;

use Corerely\ApiPlatformHelperBundle\Tests\Fixtures\Entity\Dummy;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

final class DummyFactory extends PersistentProxyObjectFactory
{
    public function withAssociations(int $quantity = 2): self
    {
        return $this->with(static fn() => ['dummyAssociations' => DummyAssociationFactory::new()->many($quantity)]);
    }

    protected function defaults(): array
    {
        return [
            'name' => self::faker()->words(asText: true),
        ];
    }

    public static function class(): string
    {
        return Dummy::class;
    }
}
