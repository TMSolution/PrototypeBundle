<?php

namespace Core\PrototypeBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class ConfigPass implements CompilerPassInterface {

    public function process(ContainerBuilder $container) {

        if (!$container->has('prototype.configurator.service')) {
            return;
        }

        $definition = $container->findDefinition(
                'prototype.configurator.service'
        );

        $taggedServices = $container->findTaggedServiceIds(
                'prototype.config'
        );

        foreach ($taggedServices as $id => $tags) {
            
          
            foreach ($tags as $attributes) {
 
                $route = null;
                if (array_key_exists('route', $attributes)) {
                    $route = str_replace("*","",$attributes['route']);
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
