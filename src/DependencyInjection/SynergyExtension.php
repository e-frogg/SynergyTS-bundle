<?php

declare(strict_types=1);

namespace Efrogg\Synergy\DependencyInjection;

use Efrogg\Synergy\Entity\SynergyEntityInterface;
use Efrogg\Synergy\Mapping\SynergyEntity;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader as DependencyInjectionLoader;

class SynergyExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        // load the configuration to inject the services
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new DependencyInjectionLoader\YamlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.yaml');

        // autoconfigure taf 'synergy.entity' for all classes with SynergyEntity attribute
        $container->registerAttributeForAutoconfiguration(
            SynergyEntity::class,
            static function (ChildDefinition $definition, SynergyEntity $attribute, \ReflectionClass $reflector) {
                $isSynergyEntity = $reflector->isSubclassOf(SynergyEntityInterface::class);
                if (!$isSynergyEntity) {
                    throw new \InvalidArgumentException(sprintf('The class %s must implement %s', $reflector->getName(), SynergyEntityInterface::class));
                }
                // add the tag
                $tagAttributes = get_object_vars($attribute);
                $definition->addTag('synergy.entity', $tagAttributes);
            }
        );
    }
}
