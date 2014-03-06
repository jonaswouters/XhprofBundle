<?php

namespace Jns\Bundle\XhprofBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\NodeInterface;

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
     * @return NodeInterface
     */
    public function getConfigTree()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('jns_xhprof');

        $rootNode
            ->validate()
                ->ifTrue(function ($v) {
                    $v = array_merge(array('xhprofio' => array('enabled' => null, 'class' => null)), $v);

                    return $v['xhprofio']['enabled'] && null === $v['xhprofio']['class'];
                })
                ->thenInvalid('If you enable xhprofio, you must define class.')
            ->end()
            ->children()
                ->scalarNode('location_web')->defaultValue('http://xhprof')->end()
                ->arrayNode('xhprof')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('enabled')->defaultFalse()->end()
                    ->end()
                ->end()
                ->arrayNode('xhprofio')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('enabled')->defaultFalse()->end()
                        ->scalarNode('manager')->defaultValue('default')->end()
                        ->scalarNode('class')->defaultNull()->end()
                    ->end()
                ->end()
                ->arrayNode('xhgui')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('enabled')->defaultFalse()->end()
                        ->scalarNode('connection')->defaultValue('mongodb://localhost')->end()
                    ->end()
                ->end()
                ->arrayNode('exclude_patterns')->prototype('scalar')->end()->end()
                ->scalarNode('sample_size')->defaultValue(1)->end()
                ->scalarNode('enabled')->defaultFalse()->end()
                ->scalarNode('request_query_argument')->defaultFalse()->end()
                ->scalarNode('response_header')->defaultValue('X-Xhprof-Url')->end()
                ->enumNode('command')
                    ->values(array('on', 'option', 'off'))
                    ->defaultValue('option')
                    ->info('on: Always profile, off: Never profile, option: only profile if option specified in command_option_name is given.')
                ->end()
                ->scalarNode('command_option_name')
                    ->defaultValue('xhprof')
                    ->info('If "command" is set to "option", this is the name of the additional option that all commands get.')
                ->end()
                ->arrayNode('command_exclude_patterns')
                    ->prototype('scalar')->end()
                    ->beforeNormalization()
                        ->ifTrue(function ($v) { return $v === null; })
                        ->then(function ($v) { return array(); })
                    ->end()
                    ->info('List of regular expressions to match commands that are not profiled.')
                ->end()
            ->end();

        return $treeBuilder->buildTree();
    }
}
