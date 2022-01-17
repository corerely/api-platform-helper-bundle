<?php
declare(strict_types=1);

use Corerely\ApiPlatformHelperBundle\Doctrine\Extension\IdentifierCollectionFilterExtension;
use Corerely\ApiPlatformHelperBundle\Doctrine\Extension\OrderByFieldsExtension;
use Corerely\ApiPlatformHelperBundle\Doctrine\Extension\PermanentFilterExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_locator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set('corerely.api_platform_helper.doctrine.permanent_filter_extension', PermanentFilterExtension::class)
        ->arg(0, tagged_locator('corerely.api_platform_helper.doctrine.permanent_filter'))
        ->tag('api_platform.doctrine.orm.query_extension.collection')
        ->tag('api_platform.doctrine.orm.query_extension.item');

    $services->set('corerely.api_platform_helper.doctrine.identifier_collection_filter_extension', IdentifierCollectionFilterExtension::class)
        ->arg(0, new Reference('router'))
        ->tag('api_platform.doctrine.orm.query_extension.collection')
    ;

    $services->set('corerely.api_platform_helper.doctrine.order_by_fields_extension', OrderByFieldsExtension::class)
        ->tag('api_platform.doctrine.orm.query_extension.collection')
        ->arg(0, new Parameter('corerely.api_platform_helper.order_by_param_name'))
        ->arg(1, new Parameter('corerely.api_platform_helper.order_by_fields'))
    ;
};
