<?php

declare(strict_types=1);

namespace PayNL\Sdk\Packages\Laminas\Hydrator;

use PayNL\Sdk\Packages\Laminas\ServiceManager\ServiceManager;

use function class_exists;

class ConfigProvider
{
    /**
     * Return configuration for this component.
     *
     * @return mixed[]
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencyConfig(),
        ];
    }

    /**
     * Return dependency mappings for this component.
     *
     * If laminas-servicemanager is installed, this will alias the HydratorPluginManager
     * to the `HydratorManager` service; otherwise, it aliases the
     * StandaloneHydratorPluginManager.
     *
     * @return string[][]
     */
    public function getDependencyConfig(): array
    {
        $hydratorManagerTarget = class_exists(ServiceManager::class)
            ? HydratorPluginManager::class
            : StandaloneHydratorPluginManager::class;

        return [
            'aliases'   => [
                'HydratorManager' => $hydratorManagerTarget,

                // Legacy Zend Framework aliases
                \Zend\Hydrator\HydratorPluginManager::class           => HydratorPluginManager::class,
                \Zend\Hydrator\StandaloneHydratorPluginManager::class => StandaloneHydratorPluginManager::class,
            ],
            'factories' => [
                HydratorPluginManager::class           => HydratorPluginManagerFactory::class,
                StandaloneHydratorPluginManager::class => StandaloneHydratorPluginManagerFactory::class,
            ],
        ];
    }
}
