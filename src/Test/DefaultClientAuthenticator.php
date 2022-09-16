<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Test;

use ApiPlatform\Symfony\Bundle\Test\Client;

class DefaultClientAuthenticator implements ClientAuthenticatorInterface
{
    public function authenticate(Client $client, object $user): Client
    {
        $client->getKernelBrowser()->loginUser($user);

        return $client;
    }
}
