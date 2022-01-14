<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Tests\Doctrine\Helper;

use Corerely\ApiPlatformHelperBundle\Doctrine\Helper\DeletedAssociationHelperTrait;
use Doctrine\ORM\EntityNotFoundException;
use PHPUnit\Framework\TestCase;

class DeletedAssociationHelperTraitTest extends TestCase
{
    use DeletedAssociationHelperTrait;

    public function testGetAssociationOrNullIfDeletedReturnAssociationIfNotDeleted(): void
    {
        $entity = new class {
            public function isDeleted(): bool
            {
                return false;
            }
        };

        $this->assertSame($entity, $this->getAssociationOrNullIfDeleted($entity));
    }

    public function testGetAssociationOrNullIfDeletedReturnNullIfDeleted(): void
    {
        $entity = new class {
            public function isDeleted(): bool
            {
                return true;
            }
        };

        $this->assertNull($this->getAssociationOrNullIfDeleted($entity));
    }

    public function testGetAssociationOrNullIfDeletedReturnNullIfEntityNotFound(): void
    {
        $entity = new class {
            public function isDeleted(): bool
            {
                throw new EntityNotFoundException('Entity not found');
            }
        };

        $this->assertNull($this->getAssociationOrNullIfDeleted($entity));
    }
}
