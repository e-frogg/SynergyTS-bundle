<?php

declare(strict_types=1);

namespace Efrogg\Synergy\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('synergy');

        /* @phpstan-ignore-next-line */
        $treeBuilder
            ->getRootNode()
                ->children()
//                    ->arrayNode('twitter')
//                        ->children()
//                            ->integerNode('client_id')->end()
//                            ->scalarNode('client_secret')->end()
//                        ->end()
//                    ->end() // twitter
                ->end()
        ;

        return $treeBuilder;
    }
}
