<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Tests\Factory;

use Corerely\ApiPlatformHelperBundle\Tests\Fixtures\Entity\DummyAssociation;
use Zenstruck\Foundry\ModelFactory;

final class DummyAssociationFactory extends ModelFactory
{

    protected function getDefaults(): array
    {
        return [
            'description' => self::faker()->text(),
        ];
    }

    protected static function getClass(): string
    {
        return DummyAssociation::class;
    }
}
