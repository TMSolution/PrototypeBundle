<?php

/**
 * Copyright (c) 2014, TMSolution
 * All rights reserved.
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace Core\PrototypeBundle\Generator;

use Doctrine\Bundle\DoctrineBundle\Mapping\DisconnectedMetadataFactory;
use ReflectionClass;
use LogicException;
use UnexpectedValueException;

/**
 * GridConfigCommand generates widget class and his template.
 * @author Mariusz Piela <mariuszpiela@gmail.com>
 */
class ServiceGenerator extends YamlGenerator {

    protected $className;
    protected $arguments;
    protected $serviceName;
    protected $service;
    protected $sufix;

    public function __construct($container, $entityName, $rootFolder, $prefix = null, $subPrefix = null, $parentEntity = null, $className, $tagName,  $arguments = [], $sufix = null) {

        parent::__construct($container, $entityName, $rootFolder,$prefix, $subPrefix,$parentEntity);
        $this->className = $className;


        $this->arguments = $arguments;
        $this->sufix = $sufix;
        $this->tagName = $tagName;
    }

    protected function getTagName() {

        return $this->tagName;
    }

    protected function getSufix() {

        return $this->sufix;
    }

    protected function getArguments() {

        return $this->arguments;
    }

    protected function getClassName() {

        return $this->className;
    }

    
    protected function getServiceName() {


        if (!$this->serviceName) {
            $this->serviceName = sprintf("%s.%s.%s", $this->convertSeparatorsToDots(), strtolower($this->getEntityShortName()), strtolower($this->getClassName()));
            

            if ($this->getSufix()) {
                $this->serviceName = $this->serviceName . "." . $this->getSufix();
            }
        }

        return $this->serviceName;
    }
    
    protected function getServiceStrategy()
    {
        $primaryStrategyName = strtolower($this->getTagName()).'.'.strtolower($this->getClassName()) . '.service.block.generator.strategy';

        //dump($primaryStrategyName);
        $secondaryStrategyName = strtolower($this->getTagName()).'.any.service.block.generator.strategy';
       // dump($secondaryStrategyName);
        
        if ($this->getContainer()->has($primaryStrategyName)) {

            $strategy = $this->getContainer()->get($primaryStrategyName);
        }
        else if($this->getContainer()->has($secondaryStrategyName))
        {
           $strategy = $this->getContainer()->get($secondaryStrategyName);       
        }
        else {
            $strategy = $this->getContainer()->get("prototype.default.any.service.block.generator.strategy");
        }
     //   dump($strategy);
        
        $strategy->setGenerator($this);
        return $strategy;
   }
    

    protected function getService(&$yml) {

        $strategy=$this->getServiceStrategy();
    
        
        
        if (!array_key_exists($this->getServiceName(), $yml['services'])) {

            
            $strategy->setEntityName($this->getEntityName());
            $strategy->setServiceName($this->getServiceName());
            $strategy->setClassNamespace($this->getClassNamespace($this->getClassName()));
            $strategy->setArguments(array_merge(["@service_container"], $this->getArguments()));
            $strategy->setTags($this->getDefaultTags());
        }

        $service = new \stdClass();
        $service->name = $strategy->getServiceName();
        $service->body = $this->createServiceBlock($strategy->getClassNamespace(), $strategy->getArguments(), $strategy->getTags());
        return $service;
    }

    protected function getDefaultTags() {

        $arguments = ['name' => "'{$this->getTagName()}'", 'prefix' => "'{$this->getPrefix()}'", 'subPrefix' => "'{$this->getSubPrefix()}'", 'entity' => "'{$this->getEntityName()}'", 'parentEntity' => "'{$this->getParentEntity()}'"];
        return [$arguments];
    }

    protected function createServiceBlock($classNamespace, $arguments = [], $tags = []) {

        return [
            'class' => "'$classNamespace'",
            'arguments' => $arguments,
            'tags' => $tags
        ];
    }

    protected function getClassNamespace($className) {
        return str_replace('\\Entity', '\\Config' . DIRECTORY_SEPARATOR . $this->getRootFolder(), $this->getEntityName()) . DIRECTORY_SEPARATOR . $className;
    }

    public function generate() {

        $yml = $this->readYml();
        $service = $this->getService($yml);

        if (!array_key_exists($service->name, $yml["services"])) {
            $yml["services"][$service->name] = $service->body;
        }
        $this->writeYml($yml, $this->getFileName());
        return $service;
    }

}
