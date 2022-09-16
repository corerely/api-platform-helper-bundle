<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Test;

use ApiPlatform\Symfony\Bundle\Test\Client;

interface ClientAuthenticatorInterface
{
    public function authenticate(Client $client, object $user): Client;
}
