<?php

namespace Efrogg\Synergy\DependencyInjection;

use Efrogg\Synergy\Mercure\Collector\ActionCollectorInterface;
use Efrogg\Synergy\Mercure\Collector\BufferedActionCollector;
use Efrogg\Synergy\Mercure\Counter\TimeBasedActionCounter;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class BufferCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // TODO : decoration seulement en cas de buffered (config)
        // decorate the mercure action collector
        $actionCollectorDefinition = $container->findDefinition(ActionCollectorInterface::class);

        // TODO : conterDefinition en fonction d'une config (time based / countBased....)
        $counterDefinition = new Definition(TimeBasedActionCounter::class);
        $decoratedDefinition = new Definition(BufferedActionCollector::class, [
            $actionCollectorDefinition,
            $counterDefinition,
        ]);
        $container->setDefinition(ActionCollectorInterface::class, $decoratedDefinition);
    }
}
