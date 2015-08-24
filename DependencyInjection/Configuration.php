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
       ->scalarNode('twig_base_index')->defaultValue('CorePrototypeBundle:Base:index.html.twig')->end()
                
        ->scalarNode('twig_element_create')->defaultValue('CorePrototypeBundle:Element:create.html.twig')->end()
        ->scalarNode('twig_element_list')->defaultValue('CorePrototypeBundle:Element:list.html.twig')->end()
        ->scalarNode('twig_element_ajaxlist')->defaultValue('CorePrototypeBundle:Element:list.ajax.html.twig')->end()
        ->scalarNode('twig_element_update')->defaultValue('CorePrototypeBundle:Element:update.html.twig')->end()
        ->scalarNode('twig_element_read')->defaultValue('CorePrototypeBundle:Element:read.html.twig')->end()
        ->scalarNode('twig_element_error')->defaultValue('CorePrototypeBundle:Element:error.html.twig')->end()
        
        ->scalarNode('twig_container_create')->defaultValue('CorePrototypeBundle:Container:create.html.twig')->end()
        ->scalarNode('twig_container_list')->defaultValue('CorePrototypeBundle:Container:list.html.twig')->end()
        ->scalarNode('twig_container_ajaxlist')->defaultValue('CorePrototypeBundle:Container:list.ajax.html.twig')->end()
        ->scalarNode('twig_container_update')->defaultValue('CorePrototypeBundle:Container:update.html.twig')->end()
        ->scalarNode('twig_container_read')->defaultValue('CorePrototypeBundle:Container:read.html.twig')->end()
        ->scalarNode('twig_container_error')->defaultValue('CorePrototypeBundle:Container:error.html.twig')->end()
        ->scalarNode('formtype_class')->defaultValue('')->end();
                
        return $treeBuilder;

    }
}
