<?php
declare(strict_types=1);

use Corerely\ApiPlatformHelperBundle\Command\CreateResourceTestCommand;
use Corerely\ApiPlatformHelperBundle\Doctrine\Extension\IdentifierCollectionFilterExtension;
use Corerely\ApiPlatformHelperBundle\Doctrine\Extension\OrderByFieldsExtension;
use Corerely\ApiPlatformHelperBundle\Doctrine\Extension\PermanentFilterExtension;
use Corerely\ApiPlatformHelperBundle\Doctrine\Filter\TextSearchFilter;
use Corerely\ApiPlatformHelperBundle\Doctrine\Filter\UuidFilter;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_locator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    // Register command
    $services->set('corerely.api_platform_helper.command.create_resource_test', CreateResourceTestCommand::class)
        ->tag('console.command')
        ->arg(0, new Parameter('kernel.project_dir'))
        ->arg(1, new Reference('api_platform.metadata.resource.name_collection_factory'))
    ;

    // Custom ApiPlatform extensions
    $services->set('corerely.api_platform_helper.doctrine.permanent_filter_extension', PermanentFilterExtension::class)
        ->arg(0, tagged_locator('corerely.api_platform_helper.doctrine.permanent_filter'))
        ->tag('api_platform.doctrine.orm.query_extension.collection')
        ->tag('api_platform.doctrine.orm.query_extension.item');

    $services->set('corerely.api_platform_helper.doctrine.identifier_collection_filter_extension', IdentifierCollectionFilterExtension::class)
        ->arg(0, new Reference('api_platform.symfony.iri_converter'))
        ->tag('api_platform.doctrine.orm.query_extension.collection')
    ;

    $services->set('corerely.api_platform_helper.doctrine.order_by_fields_extension', OrderByFieldsExtension::class)
        ->tag('api_platform.doctrine.orm.query_extension.collection')
        ->arg(0, new Parameter('corerely.api_platform_helper.order_by_param_name'))
        ->arg(1, new Parameter('corerely.api_platform_helper.order_by_fields'))
    ;

    // Custom filters
    $services->set('corerely.api_platform_helper.doctrine.text_search_filter', TextSearchFilter::class)
        ->tag('api_platform.filter')
        ->arg(0, new Reference('doctrine'))
        ->arg(1, service('logger')->nullOnInvalid())
        ->arg(2, null)
    ;

    $services->set('corerely.api_platform_helper.doctrine.uuid_filter', UuidFilter::class)
        ->tag('api_platform.filter')
        ->arg(0, new Reference('api_platform.symfony.iri_converter'))
        ->arg(1, new Reference('doctrine'))
        ->arg(2, service('logger')->nullOnInvalid())
        ->arg(3, null)
    ;
};
