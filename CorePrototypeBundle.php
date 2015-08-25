<?php

namespace Core\PrototypeBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Core\PrototypeBundle\DependencyInjection\Compiler\ConfigPass;
use Core\PrototypeBundle\DependencyInjection\Compiler\GridConfigPass;

class CorePrototypeBundle extends Bundle
{
    
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new ConfigPass());
        $container->addCompilerPass(new GridConfigPass());
    }
}
