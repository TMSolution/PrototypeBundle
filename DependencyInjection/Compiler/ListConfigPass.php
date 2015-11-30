<?php

namespace Core\PrototypeBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class ListConfigPass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container)
    {

        if (!$container->has('prototype.listconfig.configurator.service')) {
            return;
        }

        $definition = $container->findDefinition(
                'prototype.listconfig.configurator.service'
        );

        $taggedServices = $container->findTaggedServiceIds(
                'prototype.listconfig'
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

                $actionId = null;
                if (array_key_exists('actionId', $attributes)) {
                    $actionId = $attributes['actionId'];
                }



                $definition->addMethodCall(
                        'addService', array(new Reference($id), $route, $entity, $id, $parentEntity, $actionId)
                );
            }
        }
    }

}
