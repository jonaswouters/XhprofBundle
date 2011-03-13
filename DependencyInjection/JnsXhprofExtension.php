<?php

namespace Jns\Bundle\XhprofBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;

class JnsXhprofExtension extends Extension
{
    /**
     * Xml config files to load
     * @var array
     */
    protected $resources = array(
        'config' => 'config.xml',
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
        $config = array_shift($configs);
        foreach ($configs as $tmp) {
            $config = array_replace_recursive($config, $tmp);
        }

        $loader = $this->getFileLoader($container);
        $loader->load($this->resources['config']);
        $loader->load($this->resources['services']);

        foreach ($config as $key => $value) {
            $container->setParameter($this->getAlias().'.'.$key, $value);
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
    
}
