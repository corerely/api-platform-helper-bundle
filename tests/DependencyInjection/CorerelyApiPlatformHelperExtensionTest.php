<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Tests\DependencyInjection;

use Corerely\ApiPlatformHelperBundle\DependencyInjection\CorerelyApiPlatformHelperExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;

class CorerelyApiPlatformHelperExtensionTest extends AbstractExtensionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->load();
    }

    public function testLoadPermanentFilterExtension(): void
    {
        $serviceId = 'corerely.api_platform_helper.doctrine.permanent_filter_extension';
        $this->assertContainerBuilderHasServiceDefinitionWithTag($serviceId, 'api_platform.doctrine.orm.query_extension.collection');
        $this->assertContainerBuilderHasServiceDefinitionWithTag($serviceId, 'api_platform.doctrine.orm.query_extension.item');
    }

    public function testLoadCollectionExtensions(): void
    {
        $this->assertContainerBuilderHasServiceDefinitionWithTag('corerely.api_platform_helper.doctrine.identifier_collection_filter_extension', 'api_platform.doctrine.orm.query_extension.collection');
        $this->assertContainerBuilderHasServiceDefinitionWithTag('corerely.api_platform_helper.doctrine.order_by_fields_extension', 'api_platform.doctrine.orm.query_extension.collection');
    }

    protected function getContainerExtensions(): array
    {
        return [
            new CorerelyApiPlatformHelperExtension(),
        ];
    }
}
