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
    protected $parentEntity;
    protected $prefix;
    protected $subPrefix;
    protected $arguments;
    protected $serviceName;
    protected $service;
    protected $sufix;

    public function __construct($container, $entityName, $rootFolder, $className, $prefix = null, $subPrefix = null, $parentEntity = null, $arguments = [], $sufix = null) {

        parent::__construct($container, $entityName, $rootFolder);
        $this->className = $className;
        $this->parentEntity = $parentEntity;
        $this->prefix = $prefix;
        $this->subPrefix = $subPrefix;
        $this->arguments = $arguments;
        $this->sufix = $sufix;
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

    protected function getParentEntity() {

        return $this->parentEntity;
    }

    protected function getPrefix() {

        return $this->prefix;
    }

    protected function getSubPrefix() {

        return $this->subPrefix;
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

    protected function getService(&$yml) {

        $strategyName = 'prototype.' . strtolower($this->getClassName()) . '.service.block.generator.strategy';

        if ($this->getContainer()->has($strategyName)) {

            $strategy = $this->getContainer()->get($strategyName);
        } else {
            $strategy = $this->getContainer()->get("prototype.default.service.block.generator.strategy");
        }

        if (!array_key_exists($this->getServiceName(), $yml['services'])) {

            $strategy->setEntityName($this->getEntityName());
            $strategy->setServiceName($this->getServiceName());
            $strategy->setClassNamespace($this->getClassNamespace($this->getClassName()));
            $strategy->setArguments(array_merge(["@service_container"], $this->getArguments()));
            $strategy->setTags($this->getDefaultTags());
        }

        $service = new \stdClass();
        $service->name = $this->getServiceName();
        $service->body = $this->createServiceBlock($strategy->getClassNamespace(), $strategy->getArguments(), $strategy->getTags());
        return $service;
    }

    protected function getDefaultTags() {

        $arguments = ['name' => "'{$this->getClassName()}'", 'prefix' => "'{$this->getPrefix()}'", 'subPrefix' => "'{$this->getSubPrefix()}'", 'entity' => "'{$this->getEntityName()}'", 'parentEntity' => "'{$this->getParentEntity()}'"];
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

        if (!array_key_exists($service->name,$yml["services"])) {
            $yml["services"][$service->name] = $service->body;
        }
        $this->writeYml($yml, $this->getFileName());
        return $service;
    }

}
