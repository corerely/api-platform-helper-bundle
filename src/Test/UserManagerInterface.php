<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Test;

interface UserManagerInterface
{
    /**
     * Return user with role of regular user
     */
    public function getRegularUser(): object;

    /**
     * Return user with role of admin
     */
    public function getAdminUser(): object;
}
