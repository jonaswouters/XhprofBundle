<?php

namespace Jns\Bundle\XhprofBundle;

use Jns\Bundle\XhprofBundle\Compiler\AddCollectorsCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class JnsXhprofBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AddCollectorsCompilerPass());
    }
}
