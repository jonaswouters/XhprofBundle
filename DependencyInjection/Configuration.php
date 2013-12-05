<?php

namespace Jns\Bundle\XhprofBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * This class contains the configuration information for the bundle
 *
 * This information is solely responsible for how the different configuration
 * sections are normalized, and merged.
 *
 * @author Alex Kleissner <hex337@gmail.com>
 */
class Configuration
{
    /**
     * Generates the configuration tree.
     *
     * @return \Symfony\Component\DependencyInjection\Configuration\NodeInterface
     */
    public function getConfigTree()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('jns_xhprof');

        $rootNode
            ->children()
                ->scalarNode('location_web')->defaultValue('http://xhprof')->end()
                ->scalarNode('entity_manager')->defaultValue('default')->end()
                ->arrayNode('exclude_patterns')->prototype('scalar')->end()->end()
                ->scalarNode('enable_xhgui')->defaultFalse()->end()
                ->scalarNode('sample_size')->defaultValue(1)->end()
                ->scalarNode('enabled')->defaultFalse()->end()
                ->scalarNode('request_query_argument')->defaultFalse()->end()
                ->scalarNode('response_header')->defaultValue('X-Xhprof-Run-Id')->end()
            ->end();

        return $treeBuilder->buildTree();
    }
}
