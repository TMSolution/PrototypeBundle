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
                        ->arrayNode('base')
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->useAttributeAsKey('name') 
                                        ->prototype('scalar')
                                        ->end()    
                            ->end()    
                        ->end()
                        ->arrayNode('actions')
                                ->useAttributeAsKey('name')
                                ->prototype('array')
                                    ->useAttributeAsKey('name')
                                        ->prototype('variable')
                                        ->end()
                                ->end()
                        ->end()
        
//                $rootNode->children()
//                        ->arrayNode('twig')
//                            ->children()
//                                ->arrayNode('base')
//                                    ->children()
//                                        ->scalarNode('index')->end()
//                                        ->scalarNode('ajax_index')->end()
//                                    ->end()    
//                                ->end()
//                                ->arrayNode('container')
//                                    ->children()
//                                            ->scalarNode('create')->end()
//                                            ->scalarNode('update')->end()
//                                            ->scalarNode('read')->end()
//                                            ->scalarNode('view')->end()
//                                            ->scalarNode('list')->end()
//                                            ->scalarNode('ajaxlist')->end()
//                                            ->scalarNode('grid')->end()
//                                            ->scalarNode('ajaxgrid')->end()
//                                            ->scalarNode('error')->end()
//                                    ->end()
//                                ->end()
//                                ->arrayNode('element')
//                                     ->children()
//                                            ->scalarNode('create')->end()
//                                            ->scalarNode('update')->end()
//                                            ->scalarNode('read')->end()
//                                            ->scalarNode('view')->end()
//                                            ->arrayNode('list')
//                                                ->beforeNormalization()
//                                                    ->ifString()
//                                                            ->then(function ($v) { return array('name' => $v); })
//                                                     ->end()
//                                                ->children()
//                                                     ->scalarNode('name')->end()
//                                                     ->scalarNode('limit')->end()
//                                                     ->scalarNode('hydrate_mode')->end()                   
//                                                ->end()                        
//                                                                    
//                                            ->end()
//                                            ->scalarNode('ajaxlist')->end()
//                                            ->scalarNode('grid')->end()
//                                            ->scalarNode('ajaxgrid')->end()
//                                            ->scalarNode('error')->end()
//                                    ->end()    
//                                ->end()
//                            ->end()
//                        ->end()
//                  
//                  ->arrayNode('menu')
//                        ->children()                                                
//                            ->scalarNode('id')->end()
//                        ->end()                                                
//                  ->end()     
//                                                                    
//                 ->scalarNode('base_index')->defaultValue('CorePrototypeBundle:Default\Base:index.html.twig')->end()
//                ->scalarNode('base_ajax_index')->defaultValue('CorePrototypeBundle:Default\Base:ajax_index.html.twig')->end()
//                ->scalarNode('element_create')->defaultValue('CorePrototypeBundle:Default\Element:create.html.twig')->end()
//                ->scalarNode('element_list')->defaultValue('CorePrototypeBundle:Default\Element:list.html.twig')->end()
//                ->scalarNode('element_ajaxlist')->defaultValue('CorePrototypeBundle:Default\Element:list.ajax.html.twig')->end()
//                ->scalarNode('element_grid')->defaultValue('CorePrototypeBundle:Default\Element:grid.html.twig')->end()
//                ->scalarNode('element_ajaxgrid')->defaultValue('CorePrototypeBundle:Default\Element:grid.ajax.html.twig')->end()
//                ->scalarNode('element_update')->defaultValue('CorePrototypeBundle:Default\Element:update.html.twig')->end()
//                ->scalarNode('element_read')->defaultValue('CorePrototypeBundle:Default\Element:read.html.twig')->end()
//                ->scalarNode('element_error')->defaultValue('CorePrototypeBundle:Default\Element:error.html.twig')->end()
//                ->scalarNode('element_view')->defaultValue('CorePrototypeBundle:Default\Element:view.html.twig')->end()
//                ->scalarNode('container_create')->defaultValue('CorePrototypeBundle:Default\Container:create.html.twig')->end()
//                ->scalarNode('container_list')->defaultValue('CorePrototypeBundle:Default\Container:list.html.twig')->end()
//                ->scalarNode('container_grid')->defaultValue('CorePrototypeBundle:Default\Container:grid.html.twig')->end()
//                ->scalarNode('container_update')->defaultValue('CorePrototypeBundle:Default\Container:update.html.twig')->end()
//                ->scalarNode('container_read')->defaultValue('CorePrototypeBundle:Default\Container:read.html.twig')->end()
//                ->scalarNode('container_error')->defaultValue('CorePrototypeBundle:Default\sContainer:error.html.twig')->end()
//                ->scalarNode('container_view')->defaultValue('CorePrototypeBundle:Default\Container:view.html.twig')->end()
//                ->scalarNode('limit')->defaultValue(10)->end()
//                ->scalarNode('hydrateMode')->defaultValue(2)->end()
                ->arrayNode('routings')
                    ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('route')->end()
                                    ->arrayNode('route_params')
                                       ////////////////////////////////////////////////
                                       ->useAttributeAsKey('name')
                                            ->prototype('scalar')
                                            ->end()    
                                       ///////////////////////////////////////////////
                                    ->end()
                                ->end()    
                            ->end()
                    ->end()
                ;
        
//        $rootNode->children()
//                ->scalarNode('base_index')->defaultValue('CorePrototypeBundle:Default\Base:index.html.twig')->end()
//                ->scalarNode('base_ajax_index')->defaultValue('CorePrototypeBundle:Default\Base:ajax_index.html.twig')->end()
//                ->scalarNode('element_create')->defaultValue('CorePrototypeBundle:Default\Element:create.html.twig')->end()
//                ->scalarNode('element_list')->defaultValue('CorePrototypeBundle:Default\Element:list.html.twig')->end()
//                ->scalarNode('element_ajaxlist')->defaultValue('CorePrototypeBundle:Default\Element:list.ajax.html.twig')->end()
//                ->scalarNode('element_grid')->defaultValue('CorePrototypeBundle:Default\Element:grid.html.twig')->end()
//                ->scalarNode('element_ajaxgrid')->defaultValue('CorePrototypeBundle:Default\Element:grid.ajax.html.twig')->end()
//                ->scalarNode('element_update')->defaultValue('CorePrototypeBundle:Default\Element:update.html.twig')->end()
//                ->scalarNode('element_read')->defaultValue('CorePrototypeBundle:Default\Element:read.html.twig')->end()
//                ->scalarNode('element_error')->defaultValue('CorePrototypeBundle:Default\Element:error.html.twig')->end()
//                ->scalarNode('element_view')->defaultValue('CorePrototypeBundle:Default\Element:view.html.twig')->end()
//                ->scalarNode('container_create')->defaultValue('CorePrototypeBundle:Default\Container:create.html.twig')->end()
//                ->scalarNode('container_list')->defaultValue('CorePrototypeBundle:Default\Container:list.html.twig')->end()
//                ->scalarNode('container_grid')->defaultValue('CorePrototypeBundle:Default\Container:grid.html.twig')->end()
//                ->scalarNode('container_update')->defaultValue('CorePrototypeBundle:Default\Container:update.html.twig')->end()
//                ->scalarNode('container_read')->defaultValue('CorePrototypeBundle:Default\Container:read.html.twig')->end()
//                ->scalarNode('container_error')->defaultValue('CorePrototypeBundle:Default\sContainer:error.html.twig')->end()
//                ->scalarNode('container_view')->defaultValue('CorePrototypeBundle:Default\Container:view.html.twig')->end()
//                ->scalarNode('limit')->defaultValue(10)->end()
//                //HYDRATE_ARRAY
//                ->scalarNode('hydrateMode')->defaultValue(2)->end()
//                ->arrayNode('routings')
//                    ->useAttributeAsKey('name')
//                            ->prototype('array')
//                                ->children()
//                                    ->scalarNode('route')->end()
//                                    ->arrayNode('route_params')
//                                       ////////////////////////////////////////////////
//                                       ->useAttributeAsKey('name')
//                                            ->prototype('scalar')
//                                            ->end()    
//                                       ///////////////////////////////////////////////
//                                    ->end()
//                                ->end()    
//                            ->end()
//                    ->end()
//                ->scalarNode('menu_id')->end()
//                ;

        return $treeBuilder;
    }

}
