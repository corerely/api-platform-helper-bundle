<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\DependencyInjection;

use Corerely\ApiPlatformHelperBundle\Doctrine\PermanentFilter\PermanentFilterInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

final class CorerelyApiPlatformHelperExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.php');

        $container->registerForAutoconfiguration(PermanentFilterInterface::class)->addTag('corerely.api_platform_helper.doctrine.permanent_filter');
    }
}
