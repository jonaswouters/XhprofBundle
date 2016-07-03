<?php

namespace Jns\Bundle\XhprofBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;

class JnsXhprofExtension extends Extension
{
    /**
     * Xml config files to load
     * @var array
     */
    protected $resources = array(
        'services' => 'services.xml',
    );

    /**
     * Loads the services based on your application configuration.
     *
     * @param array $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $processor = new Processor();
        $configuration = new Configuration();
        $config = $processor->process($configuration->getConfigTreeBuilder()->buildTree(), $configs);

        if ($config['enabled']) {
            if (!$config['require_extension_exists'] || function_exists('xhprof_enable') || function_exists('tideways_enable')) {
                $this->loadDefaults($container);

                foreach ($config as $key => $value) {
                    $container->setParameter($this->getAlias().'.'.$key, $value);
                }
            } else {
                throw new InvalidConfigurationException("Xhprof Bundle is enabled but the xhprof extension is not enabled.");
            }
        }
    }

    public function getAlias()
    {
        return 'jns_xhprof';
    }

    /**
     * Get File Loader
     *
     * @param ContainerBuilder $container
     *
     * @return XmlFileLoader
     */
    public function getFileLoader($container)
    {
        return new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function loadDefaults($container)
    {
        $loader = $this->getFileLoader($container);

        foreach ($this->resources as $resource) {
            $loader->load($resource);
        }
    }
}
