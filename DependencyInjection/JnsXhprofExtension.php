<?php

namespace Jns\Bundle\XhprofBundle\DependencyInjection;

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
        $config = $processor->process($configuration->getConfigTree(), $configs);

        if ($config['enabled']) {
            $this->loadDefaults($container);
            
            foreach ($config as $key => $value) {
                $container->setParameter($this->getAlias().'.'.$key, $value);
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
     */
    public function getFileLoader($container)
    {
        return new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
    }

    protected function loadDefaults($container)
    {
        $loader = $this->getFileLoader($container);

        foreach ($this->resources as $resource) {
            $loader->load($resource);
        }
    }
}
