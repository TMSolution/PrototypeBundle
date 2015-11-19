<?php

namespace Core\PrototypeBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        
       
        
        
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('core_prototype');
        $rootNode->children()
        ->scalarNode('twig_base_index')->defaultValue('CorePrototypeBundle:Default\Base:index.html.twig')->end()
        ->scalarNode('twig_base_ajax_index')->defaultValue('CorePrototypeBundle:Default\Base:ajax_index.html.twig')->end()
        ->scalarNode('twig_element_create')->defaultValue('CorePrototypeBundle:Default\Element:create.html.twig')->end()
        ->scalarNode('twig_element_list')->defaultValue('CorePrototypeBundle:Default\Element:list.html.twig')->end()
        ->scalarNode('twig_element_ajaxlist')->defaultValue('CorePrototypeBundle:Default\Element:list.ajax.html.twig')->end()
        ->scalarNode('twig_element_update')->defaultValue('CorePrototypeBundle:Default\Element:update.html.twig')->end()
        ->scalarNode('twig_element_read')->defaultValue('CorePrototypeBundle:Default\Element:read.html.twig')->end()
        ->scalarNode('twig_element_error')->defaultValue('CorePrototypeBundle:Default\Element:error.html.twig')->end()
        ->scalarNode('twig_element_view')->defaultValue('CorePrototypeBundle:Default\Element:view.html.twig')->end()
        
        ->scalarNode('twig_container_create')->defaultValue('CorePrototypeBundle:Default\Container:create.html.twig')->end()
        ->scalarNode('twig_container_list')->defaultValue('CorePrototypeBundle:Default\Container:list.html.twig')->end()
        ->scalarNode('twig_container_ajaxlist')->defaultValue('CorePrototypeBundle:Default\Container:list.ajax.html.twig')->end()
        ->scalarNode('twig_container_update')->defaultValue('CorePrototypeBundle:Default\Container:update.html.twig')->end()
        ->scalarNode('twig_container_read')->defaultValue('CorePrototypeBundle:Default\Container:read.html.twig')->end()
        ->scalarNode('twig_container_error')->defaultValue('CorePrototypeBundle:Default\sContainer:error.html.twig')->end()
        ->scalarNode('twig_container_view')->defaultValue('CorePrototypeBundle:Default\Container:view.html.twig')->end()
        ;
                
        return $treeBuilder;

    }
}
