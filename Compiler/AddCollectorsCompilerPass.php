<?php

namespace Jns\Bundle\XhprofBundle\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds a data_collector tag to enabled collectors
 */
class AddCollectorsCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        // Only valid when there's a profiler definition
        if (! $container->hasDefinition('profiler')) {
            return;
        }

        $aggregate = $container->getDefinition('xhprof.aggregate_collector');

        $collectors = $container->findTaggedServiceIds('xhprof_data_collector');
        foreach ($collectors as $id => $attributes) {
            list(, $name) = explode('.', $id, 2);

            if (! $container->getParameter('jns_xhprof.' . $name . '.enabled')) {
                continue;
            }

            $collector = $container->getDefinition($id);

            $collector->clearTag('xhprof_data_collector');
            $collector->addTag('data_collector', array(
                'template' => 'JnsXhprofBundle:Collector:xhprof',
                'id'       => $name,
            ));

            $container->setDefinition($id, $collector);

            // Add this collector to the aggregate collector (command, request listeners)
            $aggregate->addMethodCall('addCollector', array(new Reference($id)));
        }
    }
}
