<?php
declare(strict_types=1);

use Corerely\ApiPlatformHelperBundle\Doctrine\Extension\PermanentFilterExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_locator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set(PermanentFilterExtension::class)
        ->args([tagged_locator('corerely.api_platform_helper.permanent_filter')]);
};
