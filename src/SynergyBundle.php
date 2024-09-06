<?php

declare(strict_types=1);

namespace Efrogg\Synergy;
use Efrogg\Synergy\DependencyInjection\BufferCompilerPass;
use Efrogg\Synergy\DependencyInjection\SynergyCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SynergyBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new SynergyCompilerPass(),PassConfig::TYPE_BEFORE_REMOVING);
        $container->addCompilerPass(new BufferCompilerPass(),PassConfig::TYPE_OPTIMIZE);
    }
}
