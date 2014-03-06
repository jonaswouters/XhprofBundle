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
     * @param array            $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $processor = new Processor();
        $configuration = new Configuration();
        $config = $processor->process($configuration->getConfigTree(), $configs);

        if ($config['enabled'] && function_exists('xhprof_enable')) {
            $this->loadDefaults($container);

            $container->setParameter($this->getAlias().'.enabled', $config['enabled']);
            $container->setParameter($this->getAlias().'.command', $config['command']);
            $container->setParameter($this->getAlias().'.xhprof.enabled', $config['xhprof']['enabled']);
            $container->setParameter($this->getAlias().'.xhprofio.enabled', $config['xhprofio']['enabled']);
            $container->setParameter($this->getAlias().'.xhprofio.manager', $config['xhprofio']['manager']);
            $container->setParameter($this->getAlias().'.xhprofio.class', $config['xhprofio']['class']);
            $container->setParameter($this->getAlias().'.xhgui.enabled', $config['xhgui']['enabled']);
            $container->setParameter($this->getAlias().'.xhgui.connection', $config['xhgui']['connection']);
            $container->setParameter($this->getAlias().'.location_web', $config['location_web']);
            $container->setParameter($this->getAlias().'.exclude_patterns', $config['exclude_patterns']);
            $container->setParameter($this->getAlias().'.sample_size', $config['sample_size']);
            $container->setParameter($this->getAlias().'.request_query_argument', $config['request_query_argument']);
            $container->setParameter($this->getAlias().'.response_header', $config['response_header']);
            $container->setParameter($this->getAlias().'.command_option_name', $config['command_option_name']);
            $container->setParameter($this->getAlias().'.command_exclude_patterns', $config['command_exclude_patterns']);
        }
    }

    public function getAlias()
    {
        return 'jns_xhprof';
    }

    /**
     * Get File Loader
     *
     * @param $container
     *
     * @return XmlFileLoader
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
