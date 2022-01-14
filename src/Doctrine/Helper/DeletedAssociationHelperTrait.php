<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Doctrine\Helper;

use Doctrine\ORM\EntityNotFoundException;

/**
 * Use when want safely return association that might be soft deleted
 */
trait DeletedAssociationHelperTrait
{
    /**
     * @template T
     *
     * @param T|null $entity
     * @return T|null
     */
    private function getAssociationOrNullIfDeleted(?object $entity): ?object
    {
        if (null === $entity) {
            return null;
        }

        if (!method_exists($entity, 'isDeleted')) {
            throw new \LogicException('Supports entities that are soft deletable');
        }

        try {
            if (!$entity->isDeleted()) {
                return $entity;
            }
        } catch (EntityNotFoundException) {
        }

        return null;
    }
}
