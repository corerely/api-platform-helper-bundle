<?php
declare(strict_types=1);

namespace App\Tests\Corerely\ApiPlatformHelperBundle\Tests\Fixtures\Resource;

use Corerely\ApiPlatformHelperBundle\Tests\Fixtures\Entity\Dummy;
use App\Factory\DummyFactory;
use App\Tests\AbstractApiTestCase;
use Symfony\Component\Uid\Uuid;

class DummyTest extends ApiTestCase
{
    private string $url = '/api/dummies';

    public function testGetCollection(): void
    {
        DummyFactory::createMany(5);

        $this->getClient()->get($this->url);

        $this->assertResponseIsSuccessful();
    }

    public function testGetItem(): void
    {
        $dummy = DummyFactory::createOne();

        $this->getClient()->get($this->url . '/' . $dummy->getUuid());

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains($this->serializeEntity($dummy, [
            /* @TODO add fields */ 
        ]));
    }

    public function testCreate(): void
    {
        $data = [
            'uuid' => (string)Uuid::v4(),
            // @TODO Add data
        ];
        
        DummyFactory::assert()->empty();

        $this->getClient()->asAdmin()->post($this->url, $data);

        $this->assertResponseIsCreated();
        DummyFactory::assert()->count(1);

        $this->assertJsonContains($data);
    }

    public function testEditItem(): void
    {
        $dummy = DummyFactory::createOne();

        $data = [
            // Edit data
        ];
        $this->getClient()->asAdmin()->put($this->url . '/' . $dummy->getUuid(), $data);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains($data);
    }

    public function testDelete(): void
    {
        $dummy = DummyFactory::createOne();

        DummyFactory::assert()->count(1);

        $this->getClient()->asAdmin()->delete($this->url . '/' . $dummy->getUuid());

        $this->assertResponseIsNoContent();
        DummyFactory::assert()->empty();
    }

    /**
     * @dataProvider collectionMethodDataProvider
     */
    public function testCollectionEndpointsAsUser(string $method): void
    {
        DummyFactory::createMany(2);

        $this->getClient()->{$method}($this->url);

        $this->assertResponseIsForbidden();
    }

    public function collectionMethodDataProvider(): iterable
    {
        yield 'get_collection' => ['get'];
        yield 'create_collection' => ['post'];
    }

    /**
     * @dataProvider itemMethodDataProvider
     */
    public function testItemEndpointsAsUser(string $method): void
    {
        $dummy = DummyFactory::createOne();

        $this->getClient()->{$method}($this->url . '/' . $dummy->getUuid());

        $this->assertResponseIsForbidden();
    }

    public function itemMethodDataProvider(): iterable
    {
        yield 'get_item' => ['get'];
        yield 'edit_item' => ['put'];
        yield 'delete_item' => ['delete'];
    }
}
