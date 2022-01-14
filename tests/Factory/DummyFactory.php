<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Tests\Factory;

use Corerely\ApiPlatformHelperBundle\Tests\Fixtures\Entity\Dummy;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @method static Dummy|Proxy createOne(array $attributes = [])
 * @method static Dummy[]|Proxy[] createMany(int $number, $attributes = [])
 */
final class DummyFactory extends ModelFactory
{
    public function withAssociations(int $quantity = 2): self
    {
        return $this->addState(static fn() => ['dummyAssociations' => DummyAssociationFactory::new()->many($quantity)]);
    }

    protected function getDefaults(): array
    {
        return [
            'name' => self::faker()->words(asText: true),
        ];
    }

    protected static function getClass(): string
    {
        return Dummy::class;
    }
}
