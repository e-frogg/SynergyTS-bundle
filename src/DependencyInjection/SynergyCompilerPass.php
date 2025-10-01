<?php

namespace Efrogg\Synergy\DependencyInjection;

use Efrogg\Synergy\Helper\EntityHelper;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SynergyCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // inject the entity definitions into the EntityHelper
        $entityHelperDefinition = $container->findDefinition(EntityHelper::class);
        $entityHelperDefinition->addMethodCall('setEntityDefinitions', [$container->findTaggedServiceIds('synergy.entity')]);
    }
}
