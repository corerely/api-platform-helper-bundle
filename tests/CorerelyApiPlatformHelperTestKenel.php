<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Tests;

use ApiPlatform\Symfony\Bundle\ApiPlatformBundle;
use Corerely\ApiPlatformHelperBundle\CorerelyApiPlatformHelperBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Zenstruck\Foundry\ZenstruckFoundryBundle;

class CorerelyApiPlatformHelperTestKenel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new DoctrineBundle(),
            new ApiPlatformBundle(),
            new ZenstruckFoundryBundle(),
            new CorerelyApiPlatformHelperBundle(),
        ];
    }

    public function getCacheDir(): string
    {
        return __DIR__.'/../var/cache'.spl_object_hash($this);
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->setParameter('kernel.project_dir', __DIR__);
        // Make all bundle services public for testing
        $container->addCompilerPass(new PublicServicePass('|api_platform_helper.*|'));

        $loader->load(__DIR__.'/config/config.yaml');
    }
}
