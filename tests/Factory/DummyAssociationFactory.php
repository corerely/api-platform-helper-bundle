<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Tests\Factory;

use Corerely\ApiPlatformHelperBundle\Tests\Fixtures\Entity\DummyAssociation;

final class DummyAssociationFactory extends \Zenstruck\Foundry\Persistence\PersistentObjectFactory
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
