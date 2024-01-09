<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Test;

interface UserManagerInterface
{
    /**
     * Return user with a role of regular user
     */
    public function getRegularUser(): object;

    /**
     * Return user with a role of admin
     */
    public function getAdminUser(): object;
}
