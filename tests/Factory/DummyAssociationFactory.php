<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Tests\Factory;

use Corerely\ApiPlatformHelperBundle\Tests\Fixtures\Entity\DummyAssociation;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

final class DummyAssociationFactory extends PersistentProxyObjectFactory
{

    protected function defaults(): array
    {
        return [
            'description' => self::faker()->text(),
        ];
    }

    public static function class(): string
    {
        return DummyAssociation::class;
    }
}
