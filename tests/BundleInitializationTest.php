<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Tests;

use Corerely\ApiPlatformHelperBundle\Command\CreateResourceTestCommand;
use Corerely\ApiPlatformHelperBundle\Doctrine\Extension\IdentifierCollectionFilterExtension;
use Corerely\ApiPlatformHelperBundle\Doctrine\Extension\OrderByFieldsExtension;
use Corerely\ApiPlatformHelperBundle\Doctrine\Extension\PermanentFilterExtension;
use Corerely\ApiPlatformHelperBundle\Doctrine\Filter\TextSearchFilter;
use Corerely\ApiPlatformHelperBundle\Doctrine\Filter\UuidFilter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BundleInitializationTest extends KernelTestCase
{
    private static ContainerInterface $testContainer;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
        self::$testContainer = self::getContainer();
    }

    public function testPermanentFilterExtensionService(): void
    {
        self::assertServiceConfigured('corerely.api_platform_helper.doctrine.permanent_filter_extension', PermanentFilterExtension::class);
    }

    public function testIdentifierCollectionFilterExtensionService(): void
    {
        self::assertServiceConfigured('corerely.api_platform_helper.doctrine.identifier_collection_filter_extension', IdentifierCollectionFilterExtension::class);
    }

    public function testOrderByFieldsExtensionService(): void
    {
        self::assertServiceConfigured('corerely.api_platform_helper.doctrine.order_by_fields_extension', OrderByFieldsExtension::class);
    }

    public function testTextSearchFilterService(): void
    {
        self::assertServiceConfigured('corerely.api_platform_helper.doctrine.text_search_filter', TextSearchFilter::class);
    }

    public function testUuidFilterService(): void
    {
        self::assertServiceConfigured('corerely.api_platform_helper.doctrine.uuid_filter', UuidFilter::class);
    }

    public function testCreateResourceTestCommandService(): void
    {
        self::assertServiceConfigured('corerely.api_platform_helper.command.create_resource_test', CreateResourceTestCommand::class);
    }

    private static function assertServiceConfigured(string $id, string $className): void
    {
        self::assertTrue(self::$testContainer->has($id));

        $service = self::$testContainer->get($id);
        self::assertInstanceOf($className, $service);
    }
}
