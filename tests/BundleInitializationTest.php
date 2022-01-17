<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Tests;

use Corerely\ApiPlatformHelperBundle\Doctrine\Extension\IdentifierCollectionFilterExtension;
use Corerely\ApiPlatformHelperBundle\Doctrine\Extension\OrderByFieldsExtension;
use Corerely\ApiPlatformHelperBundle\Doctrine\Extension\PermanentFilterExtension;
use Corerely\ApiPlatformHelperBundle\Doctrine\Filter\TextSearchFilter;
use Corerely\ApiPlatformHelperBundle\Doctrine\Filter\UuidFilter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BundleInitializationTest extends KernelTestCase
{
    private ContainerInterface $container;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
        $this->container = self::getContainer();
    }

    public function testPermanentFilterExtensionService(): void
    {
        $this->assertServiceConfigured('corerely.api_platform_helper.doctrine.permanent_filter_extension', PermanentFilterExtension::class);
    }

    public function testIdentifierCollectionFilterExtensionService(): void
    {
        $this->assertServiceConfigured('corerely.api_platform_helper.doctrine.identifier_collection_filter_extension', IdentifierCollectionFilterExtension::class);
    }

    public function testOrderByFieldsExtensionService(): void
    {
        $this->assertServiceConfigured('corerely.api_platform_helper.doctrine.order_by_fields_extension', OrderByFieldsExtension::class);
    }

    public function testTextSearchFilterService(): void
    {
        $this->assertServiceConfigured('corerely.api_platform_helper.doctrine.text_search_filter', TextSearchFilter::class);
    }

    public function testUuidFilterService(): void
    {
        $this->assertServiceConfigured('corerely.api_platform_helper.doctrine.uuid_filter', UuidFilter::class);
    }

    private function assertServiceConfigured(string $id, string $className): void
    {
        $this->assertTrue($this->container->has($id));

        $service = $this->container->get($id);
        $this->assertInstanceOf($className, $service);
    }
}
