<?php

namespace Core\PrototypeBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Core\PrototypeBundle\DependencyInjection\Compiler\ConfigPass;
use Core\PrototypeBundle\DependencyInjection\Compiler\GridBuilderPass;

class CorePrototypeBundle extends Bundle// implements BundleDependencyInterface
{
    
    
    
    
    public function registerDependencies()
    {
        return array(
            new FOS\JsRoutingBundle\FOSJsRoutingBundle(),
            new APY\DataGridBundle\APYDataGridBundle(),
            new BeSimple\I18nRoutingBundle\BeSimpleI18nRoutingBundle(),
            
            new TMSolution\DataGridBundle\TMSolutionDataGridBundle(),
            new Core\ModelBundle\CoreModelBundle(),
            new Core\ClassMapperBundle\CoreClassMapperBundle(),
 
        );
    }
    
               
               
    public function build(ContainerBuilder $container)
    {
      
        parent::build($container);
        $container->addCompilerPass(new ConfigPass());
        $container->addCompilerPass(new GridBuilderPass());
    }
}
