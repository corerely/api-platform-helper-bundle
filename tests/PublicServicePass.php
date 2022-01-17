<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Tests;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PublicServicePass implements CompilerPassInterface
{

    /**
     * @param string $regex A regex to match the services that should be public.
     */
    public function __construct(private string $regex = '|.*|')
    {
    }

    public function process(ContainerBuilder $container): void
    {
        foreach ($container->getDefinitions() as $id => $definition) {
            if (preg_match($this->regex, $id)) {
                $definition->setPublic(true);
            }
        }

        foreach ($container->getAliases() as $id => $alias) {
            if (preg_match($this->regex, $id)) {
                $alias->setPublic(true);
            }
        }
    }
}
