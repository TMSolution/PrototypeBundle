<?php

namespace Core\PrototypeBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class GridBuilderPass implements CompilerPassInterface
{
       public function process(ContainerBuilder $container) {

        if (!$container->has('prototype.gridbuilder.configurator.service')) {
            return;
        }

        $definition = $container->findDefinition(
                'prototype.gridbuilder.configurator.service'
        );

        $taggedServices = $container->findTaggedServiceIds(
                'prototype.gridbuilder'
        );
        
       
        foreach ($taggedServices as $id => $tags) {
          
            foreach ($tags as $attributes) {
 
                $route = null;
                if (array_key_exists('route', $attributes)) {
                    $route = $attributes['route'];
                }

                $entity = null;
                if (array_key_exists('entity', $attributes)) {
                    $entity = $attributes['entity'];
                }

                $definition->addMethodCall(
                'addService',
                array(new Reference($id),$route,$entity)
                );
            }
        }
    }
}