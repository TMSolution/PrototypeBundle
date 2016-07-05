<?php

namespace Core\PrototypeBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class IdFinderPass implements CompilerPassInterface {

    public function process(ContainerBuilder $container) {


        if (!$container->has('prototype.idfinder.configurator.service')) {
            return;
        }

        $definition = $container->findDefinition(
                'prototype.idfinder.configurator.service'
        );

        $taggedServices = $container->findTaggedServiceIds(
                'prototype.idfinder'
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

                $parentEntity = null;
                if (array_key_exists('parentEntity', $attributes)) {
                    $parentEntity = $attributes['parentEntity'];
                }

                $prefix = null;
                if (array_key_exists('prefix', $attributes)) {
                    $prefix = $attributes['prefix'];
                }

                $subPrefix = null;
                if (array_key_exists('subPrefix', $attributes)) {
                    $subPrefix = $attributes['subPrefix'];
                }


                $definition->addMethodCall(
                        'addService', array(new Reference($id), $route, $entity, $id, $parentEntity, $prefix, $subPrefix)
                );
            }
        }
    }

}
