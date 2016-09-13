<?php

namespace Core\PrototypeBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class FormTypePass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container)
    {

        if (!$container->has('prototype.formtype.configurator.service')) {
            return;
        }

        $definition = $container->findDefinition(
                'prototype.formtype.configurator.service'
        );


        $taggedServices = $container->findTaggedServiceIds(
                'prototype.formtype'
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



                $definition->addMethodCall(
                        'addService', array(new Reference($id), $route, $entity, $id, $parentEntity, $prefix)
                );


          
            }
        }
    }

}
