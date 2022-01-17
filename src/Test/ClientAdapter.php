<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Test;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Client;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Zenstruck\Foundry\Proxy;

class ClientAdapter
{
    private ?object $user = null;

    private bool $isMultipartFormData = false;

    public function __construct(private Client $client, private UserManagerInterface $userManager)
    {
        $this->client->setDefaultOptions([
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);
    }

    public function asMultipartFormData(): static
    {
        $this->isMultipartFormData = true;

        return $this;
    }

    public function catchExceptionOff(): static
    {
        $this->client->getKernelBrowser()->catchExceptions(false);

        return $this;
    }

    public function asUser(object $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function asAdmin(): static
    {
        $this->user = $this->userManager->getAdminUser();

        return $this;
    }

    public function get(string $url, array $queryParams = null): ResponseInterface
    {
        if ($queryParams) {
            $url .= str_contains($url, '?') ? '&' : '?' . http_build_query($queryParams);
        }

        return $this->authenticateClient()->request('GET', $url);
    }

    public function post(string $url, array $data = [], array $files = null): ResponseInterface
    {
        return $this->authenticateClient()->request('POST', $url, $this->createClientOptions($data, $files));
    }

    public function put(string $url, array $data = [], array $files = null): ResponseInterface
    {
        return $this->authenticateClient()->request('PUT', $url, $this->createClientOptions($data, $files));
    }

    public function delete(string $url): ResponseInterface
    {
        return $this->authenticateClient()->request('DELETE', $url);
    }

    public function authenticateClient(object $user = null): Client
    {
        $user ??= $this->user;

        if (null === $user) {
            $user = $this->userManager->getRegularUser();
        }

        if ($user instanceof Proxy) {
            $user = $user->object();
        }

        $this->client->getKernelBrowser()->loginUser($user);

        return $this->client;
    }

    private function createClientOptions(array $data, array $files = null): array
    {
        if ($this->isMultipartFormData) {
            return [
                'headers' => ['Content-Type' => 'multipart/form-data'],
                'extra' => [
                    'parameters' => array_map(static fn($item) => is_array($item) ? json_encode($item) : $item, $data),
                    'files' => $files,
                ],
            ];
        }

        if ($files) {
            throw new \LogicException('Files given, but form is not "multipart/form-data"');
        }

        return [
            'json' => $data,
        ];
    }
}
