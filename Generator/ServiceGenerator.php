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
abstract class ServiceGenerator extends AbstractGenerator {

    protected $container;
    protected $shortEntityName;
    protected $entityName;
    protected $templatePath;
    protected $fileName;
    protected $rootFolder;
    protected $withAssociated;
    protected $directory;
    protected $bundleName;
    protected $yml;

    public function __construct($entityName, $rootFolder, $applicationName, $rootSpace, $tag, $route, $parentEntity, $withAssociated) {

        $this->entityName = $entityName;

        $this->applicationName = $applicationName;
        $this->rootFolder = $rootFolder;
        $this->rootSpace = $rootSpace;
        $this->tag = $tag;
        $this->route = $route;
        $this->parentEntity = $parentEntity;
        $this->withAssociated = $withAssociated;
    }

    public function getBundleName() {
        $model = $this->getContainer()->get("model_factory")->getModel($this->getEntityName());
        $metadata = $model->getMetadata();
        return str_replace('\\', '', str_replace('\Entity', null, $metadata->namespace));
    }

    public function getEntityName() {
        return $this->entityName;
    }

    public function getApplicationName() {
        return $this->applicationName;
    }

    public function getRootFolder() {
        return $this->rootFolder;
    }

    public function getRootSpace() {
        return $this->rootSpace;
    }

    public function getTag() {
        return $this->tag;
    }

    public function getRoute() {
        return $this->route;
    }

    public function getParentEntity() {
        return $this->parentEntity;
    }

    public function getWithAssociated() {
        return $this->withAssociated;
    }
    
    public function getYml()
    {
        return $this->yml;
    }
    

    protected function getDirectoryPath() {
        return "Resources" . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . $this->getRootFolder() . DIRECTORY_SEPARATOR . $this->getEntityShortName() . DIRECTORY_SEPARATOR;
    }

    protected function getConfigFilePath($manager, $input) {
        $bundle = $this->getApplication()->getKernel()->getBundle($input->getOption('entity'));
        $bundleMetadata = $manager->getBundleMetadata($bundle);
        return str_replace('/', DIRECTORY_SEPARATOR, $bundleMetadata->getPath()) . DIRECTORY_SEPARATOR . $bundleMetadata->getNamespace() . DIRECTORY_SEPARATOR . 'Resources/config/prototype.services.yml';
    }

    protected function readYml($configFullPath) {
        try {
            if (file_exists($configFullPath)) {
                $yaml = new Parser();
                $yamlArr = $yaml->parse(file_get_contents($configFullPath));
                if ($yamlArr === NULL) {
                    $yamlArr = ['parameters' => [], 'services' => []];
                }
            } else {
                $yamlArr = ['parameters' => [], 'services' => []];
            }

            return $this->repairApostrophes($yamlArr);
        } catch (\Exception $e) {
            throw new \Exception('Error reading yml file.');
        }
        
        $this->yml=$yamlArr;
        return $this->yml;
    }

    protected function writeYml($fileName, $yamlArr, $output) {

        $yaml = new Dumper();
        $yamlData = $yaml->dump($yamlArr, 4, 0, false, true);

        //die($yamlData);
        file_put_contents($fileName, str_replace("'@service_container'", "@service_container", $yamlData));
        $output->writeln("Services configuration file <info>" . $fileName . "</info> generated.");
    }

    protected function repairApostrophes(&$yamlArr) {
        if (is_array($yamlArr['services'])) {
            foreach ($yamlArr['services'] as $key => $value) {

                foreach ($value as $key2 => $value2) {

                    if ($key2 == 'class' && substr($value2, 0, 1) != "'") {
                        $yamlArr['services'][$key][$key2] = "'" . $value2 . "'";
                    }
                    if ($key2 == 'tags' && is_array($value2) && substr($value2[0]['entity'], 0, 1) != "'") {
                        $yamlArr['services'][$key][$key2][0]['entity'] = "'" . $value2[0]['entity'] . "'";

                        if (array_key_exists('parentEntity', $value2[0])) {
                            $yamlArr['services'][$key][$key2][0]['parentEntity'] = "'" . $value2[0]['parentEntity'] . "'";
                        }
                    }
                }
            }
        }
    }

    protected function checkParametersKeyExist($yamlArr, $key, $output) {
        $output->writeln("Check parameters key: <info>" . $key . "</info>.");
        if (is_array($yamlArr['parameters'])) {
            return array_key_exists($key, $yamlArr['parameters']);
        }
    }

    protected function addParameters(&$yamlArr, $entity, $tag, $bundleName, $rootSpace, $objectName, $output, $associated = false, $entityName) {

        $entityName = str_replace('\\', '', $entityName);
        $parametersName = '';
        if (!$this->checkParametersKeyExist($yamlArr, $parametersName, $output)) {

            switch ($tag) {
                case 'prototype.config':


                    if ($associated) {
                        $parametersName = $this->createParametersName($entity, $tag, $rootSpace);
                        $yamlArr['parameters'][$parametersName] = [
                            'twig_element_read' => /* $bundleName */$entityName . ':' . $rootSpace . '\\' . $objectName . '\\Element:read.html.twig',
                            'twig_element_create' => /* $bundleName */$entityName . ':' . $rootSpace . '\\' . $objectName . '\\Element:create.html.twig',
                            'twig_element_update' => /* $bundleName */$entityName . ':' . $rootSpace . '\\' . $objectName . '\\Element:update.html.twig',
                            'twig_element_view' => /* $bundleName */$entityName . ':' . $rootSpace . '\\' . $objectName . '\\Element:view.html.twig',
                            'twig_element_list' => /* $bundleName */$entityName . ':' . $rootSpace . '\\' . $objectName . '\\Element:list.html.twig'
                        ];
                    } else {
                        $parametersName = $this->createParametersName($entity, $tag, $rootSpace);
                        $yamlArr['parameters'][$parametersName] = [
                            'twig_container_view' => /* $bundleName */$entityName . ':' . $rootSpace . '\\' . $objectName . '\\Container:view.html.twig',
                            'twig_container_read' => /* $bundleName */$entityName . ':' . $rootSpace . '\\' . $objectName . '\\Container:read.html.twig',
                            'twig_container_create' => /* $bundleName */$entityName . ':' . $rootSpace . '\\' . $objectName . '\\Container:create.html.twig',
                            'twig_container_update' => /* $bundleName */$entityName . ':' . $rootSpace . '\\' . $objectName . '\\Container:update.html.twig',
                            'twig_container_grid' => /* $bundleName */$entityName . ':' . $rootSpace . '\\' . $objectName . '\\Container:grid.html.twig',
                            'twig_container_list' => /* $bundleName */$entityName . ':' . $rootSpace . '\\' . $objectName . '\\Container:list.html.twig',
                            'twig_element_view' => /* $bundleName */$entityName . ':' . $rootSpace . '\\' . $objectName . '\\Element:view.html.twig',
                            'twig_element_read' => /* $bundleName */$entityName . ':' . $rootSpace . '\\' . $objectName . '\\Element:read.html.twig',
                            'twig_element_create' => /* $bundleName */$entityName . ':' . $rootSpace . '\\' . $objectName . '\\Element:create.html.twig',
                            'twig_element_update' => /* $bundleName */$entityName . ':' . $rootSpace . '\\' . $objectName . '\\Element:update.html.twig',
                            'twig_element_list' => /* $bundleName */$entityName . ':' . $rootSpace . '\\' . $objectName . '\\Element:list.html.twig'
                        ];
                    }
                    break;
                case 'prototype.formtype':
                    break;
            }
        } else {
            $output->writeln("Twig parameters for <comment>" . $parametersName . "</comment> already exist.");
        }

        return $parametersName;
    }

    protected function checkServicesKeyExist($yamlArr, $key, $output) {
        $output->writeln("Check service key: <info>" . $key . "</info>.");
        if (is_array($yamlArr['services'])) {
            return array_key_exists($key, $yamlArr['services']);
        }
        return false;
    }

    protected function createServiceName($entity, $tag, $associationName = null, $rootSpace = null) {

        $tagArr = explode('.', $tag);
        unset($tagArr[0]);
        $tagName = implode('.', $tagArr);

        switch ($tag) {
            case 'prototype.config':
            case 'prototype.gridconfig':
            case 'prototype.listconfig':
            case 'prototype.viewconfig':
            case 'prototype.formtype':
                $serviceName = str_replace('\\', '.', str_replace('bundle\\entity', '', strtolower($entity)));
                $serviceName .= '.' . strtolower($tagName);


                if ($associationName) {
                    $original = explode('.', $serviceName);

                    if ($rootSpace && $rootSpace != $associationName) {
                        $inserted = [strtolower($rootSpace . '.' . $associationName)];
                    } else {
                        $inserted = [strtolower($associationName)];
                    }
                    array_splice($original, 2, 0, $inserted);
                    $serviceName = implode('.', $original);
                }

                break;
        }

        return $serviceName;
    }

    protected function addService() {
        $serviceName = $this->createServiceName($entity, $tag, $associatedName, $rootSpace);
        $className = $this->createClassName($entity, $tag, $entityName, $associatedName, $rootSpace, $entityName);
        if (!$this->checkServicesKeyExist($yamlArr, $serviceName, $output)) {
            switch ($this->getTag()) {
                case 'prototype.config':
                    $this->createService($className, ["%prototype_config_params%", "%$parameterName%"]);
                    break;
                case 'prototype.listconfig':
                    $this->createService($className);
                    break;
                case 'prototype.viewconfig':
                    $this->createService($className,["@service_container"]);
                    break;
                case 'prototype.formtype':
                    $this->createService($className);
                    break;
            }
        } else {
            
        }
    }

    protected function createService($serviceName, $className,$parameters=[]) {
        $yamlArr['services'][$serviceName] = [
            'class' => "'$className'",
            'arguments' => $parameters,
            'tags' => [['name' => "'{$this->getTag()}'", 'prefix' => "'{$this->getPrefix()}'", 'subPrefix' => "'{$this->getSubPrefix()}'", 'entity' => "'{$this->getEntityName()}'", 'parentEntity' => "'{$this->getParentEntity()}'"]]
        ];
    }

    protected function createParametersName($entity, $tag, $associationName = null) {

        $tagArr = explode('.', $tag);
        unset($tagArr[0]);
        $tagName = implode('.', $tagArr);

        switch ($tag) {
            case 'prototype.config':
                $parametersName = 'parameters.' . str_replace('\\', '.', str_replace('bundle\\entity', '', strtolower($entity)));
                $parametersName .= '.' . strtolower($tagName);
                if ($associationName) {
                    $original = explode('.', $parametersName);
                    $inserted = [strtolower(str_replace('\\', '.', $associationName))];
                    array_splice($original, 3, 0, $inserted);
                    $parametersName = implode('.', $original);
                }
                break;
        }

        return str_replace('.config', '', $parametersName);
    }

    protected function createClassName($entity, $tag, $entityName, $associationName = null, $rootSpace = null) {

        switch ($tag) {
            case 'prototype.config':
                $className = 'Core\\PrototypeBundle\\Service\\Config';
                break;
            case 'prototype.listconfig':

                $className = str_replace('\\Entity', '\\Config', $entity) . '\\ListConfig';
                $classNamePrefix = substr($className, 0, strpos($className, 'Bundle', 0) + 6);
                $className = str_replace($classNamePrefix, $entityName, $className);

                if ($associationName) {

                    if ($rootSpace && $rootSpace != $associationName) {
                        $className = str_replace('\\Config\\', '\\Config\\' . $rootSpace . '\\' . $associationName . '\\', $className);
                    } else {
                        $className = str_replace('\\Config\\', '\\Config\\' . $associationName . '\\', $className);
                    }
                }
                break;
            case 'prototype.viewconfig':

                $className = str_replace('\\Entity', '\\Config', $entity) . '\\ViewConfig';
                $classNamePrefix = substr($className, 0, strpos($className, 'Bundle', 0) + 6);
                $className = str_replace($classNamePrefix, $entityName, $className);

                if ($associationName) {

                    if ($rootSpace && $rootSpace != $associationName) {
                        $className = str_replace('\\Config\\', '\\Config\\' . $rootSpace . '\\' . $associationName . '\\', $className);
                    } else {
                        $className = str_replace('\\Config\\', '\\Config\\' . $associationName . '\\', $className);
                    }
                }
                break;
            case 'prototype.formtype':

                $className = str_replace('\\Entity', '\\Config', $entity) . '\\FormType';
                $classNamePrefix = substr($className, 0, strpos($className, 'Bundle', 0) + 6);
                $className = str_replace($classNamePrefix, $entityName, $className);


                if ($rootSpace && $rootSpace != $associationName) {
                    $className = str_replace('\\Config\\', '\\Config\\' . $rootSpace . '\\' . $associationName . '\\', $className);
                } else {
                    $className = str_replace('\\Config\\', '\\Config\\' . $associationName . '\\', $className);
                }
                break;
        }

        return $className;
    }

    public function getInstance($entityName,$directory){
    
                return new static($this->getContainer(), $entityName , $this->getTemplatePath(), $this->getFileName(), $directory, FALSE);
    }
    
    
    protected function runAssociatedObjectsRecursively() {
       
        $associations = [];
        $this->getFieldsInfo();
        foreach ($fieldsInfo as $key => $value) {

            $associationTypes = ["OneToMany", "ManyToMany"];
            $field = $fieldsInfo[$key];
            if (array_key_exists("association", $field) && in_array($field["association"], $associationTypes)) {
                $arr = explode('\\', $input->getOption('entity'));
                $rootLast = array_pop($arr);
                $arr = explode('\\', $value['object_name']);
                $last = array_pop($arr);
                $directory=$this->getRootFolder(). DIRECTORY_SEPARATOR . $objectName;
                $generator=$this->getInstance($value['object_name'],$directory);
                $generator->generate();
            }
        }
    }

    protected function generate() {

        $this->readYml($this->getDirectory() . DIRECTORY_SEPARATOR . $this->getEntityShortName());
        
        
            $parameterName = $this->addParameters();
            $this->addService();
        
        if (true === $input->getOption('withAssociated')) {
            $this->runAssociatedObjectsRecursively();
        }
     $this->writeYml($ymlArr);
    }

}
