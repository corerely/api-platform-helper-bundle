<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Test;

trait FactoriesProxyHelper
{
    private function getRealEntityObject(object $entity): object
    {
        // If Zenstruck Foundry v1.x is used
        if (interface_exists('Zenstruck\Foundry\Proxy') && $entity instanceof \Zenstruck\Foundry\Proxy) {
            return $entity->object();
        }

        // For Zenstruck Foundry v2.x
        if (interface_exists('Zenstruck\Foundry\Persistence\Proxy') && $entity instanceof \Zenstruck\Foundry\Persistence\Proxy) {
            return $entity->_real();
        }

        return $entity;
    }

    private function isEntityProxy(object $entity): bool
    {
        // If Zenstruck Foundry v1.x is used
        if (interface_exists('Zenstruck\Foundry\Proxy') && $entity instanceof \Zenstruck\Foundry\Proxy) {
            return true;
        }

        // For Zenstruck Foundry v2.x
        if (interface_exists('Zenstruck\Foundry\Persistence\Proxy') && $entity instanceof \Zenstruck\Foundry\Persistence\Proxy) {
            return true;
        }

        return false;
    }
}
