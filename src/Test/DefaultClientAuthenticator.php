<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Test;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Client;

class DefaultClientAuthenticator implements ClientAuthenticatorInterface
{
    public function authenticate(Client $client, object $user): Client
    {
        $client->getKernelBrowser()->loginUser($user);

        return $client;
    }
}
