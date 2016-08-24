<?php

/**
 * Copyright (c) 2014, TMSolution
 * All rights reserved.
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace Core\PrototypeBundle\Generator\ServiceStrategy;


class DefaultStrategy {

    
    protected $generator;
    protected $entityName;
    protected $classNamespace;
    protected $arguments=[];
    protected $tags=[];
    protected $serviceName;
    protected $container;
    
    
    public function __construct($container)
    {
        $this->container=$container;
        
    }
    
    
    
    public function setGenerator($generator)
    {
        
        $this->generator=$generator;
    }
    
    
    
    public function getContainer() {
        
        return $this->container;
        
    }
    public function setEntityName($entityName) {
        
        $this->entityName=$entityName;
        
    }
    
    public function getEntityName() {
        
        return $this->entityName;
        
    }
    
    public function setServiceName($serviceName) {
        
        $this->serviceName=$serviceName;
    }
    
    public function getServiceName() {
        
        return $this->serviceName;
    }
    
    function getClassNamespace() {
        return $this->classNamespace;
    }

    function getArguments() {
        return $this->arguments;
    }

    function getTags() {
        return $this->tags;
    }

    function setClassNamespace($classNamespace) {
        $this->classNamespace = $classNamespace;
    }

    function setArguments($arguments) {
        $this->arguments = $arguments;
    }

    function setTags($tags) {
        $this->tags = $tags;
    }


    
    
    
    
    

}
