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
abstract class AbstractGenerator {

    protected $container;
    protected $shortEntityName;
    protected $entityName;
    protected $templatePath;
    protected $fileName;
    protected $rootFolder;
    protected $withAssociated;
    protected $directory;

    public function __construct($container, $entityName, $templatePath, $fileName, $rootFolder, $withAssociated) {
        $this->container = $container;
        $this->entityName = $this->noramlizeEntityName($entityName);
        $this->templatePath = $templatePath;
        $this->fileName = $fileName;
        $this->rootFolder = $rootFolder;
        $this->withAssociated = $withAssociated;
    }

    protected function getContainer() {
        return $this->container;
    }
    
    
    protected function getRootFolder() {
        return $this->rootFolder;
    }
    
    
    protected function getFileName() {
        return $this->fileName;
    }
    
    protected function getTemplatePath() {
        return $this->templatePath;
    }
    
    protected function getWithAssociated() {
        return $this->withAssociated;
    }

    protected function noramlizeEntityName($entityName)
    {
            $doctrine = $this->getContainer()->get('doctrine');
            $entityName = str_replace('/', '\\', $entityName);
           
            if (($position = strpos($entityName, ':')) !== false) {
                $entityName = $doctrine->getAliasNamespace(substr($entityName, 0, $position)) . '\\' . substr($entityName, $position + 1);
            }
         
            return $entityName;
    }
    
    protected function getEntityName() {

            return $this->entityName;
    }
    

    protected function getFieldsInfo() {
        $model = $this->getContainer()->get("model_factory")->getModel($this->getEntityName());
        return $model->getFieldsInfo();
    }

    protected function getEntityShortName() {

        $entityReflection = new ReflectionClass($this->getEntityName());
        return  $shortEntityName = $entityReflection->getShortName();
    }

    protected function getClassPath() {
        $manager = new DisconnectedMetadataFactory($this->getContainer()->get('doctrine'));
        $classPath = $manager->getClassMetadata($this->getEntityName())->getPath();
        return $classPath;
    }

    protected function getGridConfigNamespaceName($entityName) {

        $entityNameArr = explode("\\", str_replace("Entity", "Grid", $entityName));
        unset($entityNameArr[count($entityNameArr) - 1]);
        return implode("\\", $entityNameArr);
    }



    abstract protected function getDirectoryPath();

    protected function getDirectory() {

        if (!$this->directory) {
        
            /*
            if ($path) {
                $entityNamespace = $entityNamespace . DIRECTORY_SEPARATOR . $path;
            }*/

            $entityReflection = new ReflectionClass($this->getEntityName());
            $entityNamespace = $entityReflection->getNamespaceName();
         
            $directory = str_replace("\\", DIRECTORY_SEPARATOR, ($this->getClassPath() . "\\" . $entityNamespace));
            $this->directory = $this->replaceLast("Entity", $this->getDirectoryPath($this->getEntityShortName()), $directory);

                if ( is_dir($this->directory) == false && mkdir($this->directory, 0777, true) == false) {
                    throw new UnexpectedValueException("Creating directory failed: " . $directory);
                }
            
        }
        return $this->directory;
    }

    protected function calculateFileName($entityReflection) {

        $fileName = $this->replaceLast("Entity", "Grid", $entityReflection->getFileName());
        return $fileName;
    }

    protected function isFileNameBusy($fileName) {
        if (file_exists($fileName) == true) {
            throw new LogicException("File " . $fileName . " exists!");
        }
        return false;
    }

    protected function replaceLast($search, $replace, $subject) {
        $position = strrpos($subject, $search);
        if ($position !== false) {
            $subject = \substr_replace($subject, $replace, $position, strlen($search));
        }
        return $subject;
    }

    protected function getAssociatedObjects($fieldsInfo) {

        $associations = [];
        foreach ($fieldsInfo as $key => $value) {

            $associationTypes = ["OneToMany", "ManyToMany", "OneToOne"];
            $field = $fieldsInfo[$key];
            if (array_key_exists("association", $field) && in_array($field["association"], $associationTypes)) {
                $associations[$key] = $field;
                $associations[$key]["object_name"] = str_replace('\\', '\\\\', $field["object_name"]);
                $associations[$key]["object_name_stripslashes"] = $field["object_name"];
            }
        }

        return $associations;
    }

    public function generate() {

        $fileName = $this->createFile();

        if (true === $this->getWithAssociated()) {

            $this->generateAssociatedFiles();
        }

        return $fileName;
    }

    protected function getTemplateData() {

        $associations = $this->getAssociatedObjects($this->extendFieldsInfo());
        
        $entityReflection = new ReflectionClass($this->getEntityName());
        $entityNamespace = $entityReflection->getNamespaceName();
        $entityShortName = $entityReflection->getShortName();

        $lowerNameSpaceForTranslate = str_replace('bundle.entity', '', str_replace('\\', '.', strtolower($entityNamespace)));
        return
                [
                    
                    "entityName" => $this->getEntityName(),
                    "objectName" => $entityShortName,
                    "fieldsInfo" => $this->extendFieldsInfo(),
                    "associations" => $associations,
                    "lowerNameSpaceForTranslate" => $lowerNameSpaceForTranslate, /*                     * @todo do wywalenia */
                    "that" => $this /* @todo do wywalenia */
        ];
    }

    

    protected function renderFile() {

        $templating = $this->getContainer()->get('templating');
        return $templating->render($this->getTemplatePath(), $this->getTemplateData());
    }

    protected function createFile() {

        $filePath = $this->getDirectory() . DIRECTORY_SEPARATOR . $this->getFileName();
        $this->isFileNameBusy($this->getFileName());
        file_put_contents($filePath, $this->renderFile());

        return $filePath;
    }
    
    public function getInstance($entityName,$directory){
    
                return new static($this->getContainer(), $entityName , $this->getTemplatePath(), $this->getFileName(), $directory, FALSE);
    }

    protected function generateAssociatedFiles() {
        $associations = [];
       
        
        $fieldsInfo=$this->getFieldsInfo();
        
        foreach ($fieldsInfo as $key => $value) {

            $associationTypes = ["OneToMany", "ManyToMany"];
            $field = $fieldsInfo[$key];
            if (array_key_exists("association", $field) && in_array($field["association"], $associationTypes)) {

                $model = $this->getContainer()->get("model_factory")->getModel($value['object_name']);
                $assocObjectFieldsInfo = $model->getFieldsInfo();

                $arr = explode('\\', $value['object_name']);
                //$path = array_pop($arr);

                $directory = $this->getRootFolder() . DIRECTORY_SEPARATOR . $this->getEntityShortName();
                
                $generator= $this->getInstance($value['object_name'],$directory);
                $generator->generate();
                
               // $this->createFile($value['object_name'], $assocObjectFieldsInfo, $this->getRootFolder() . DIRECTORY_SEPARATOR . $entityShortName, $entityName);
            }
        }
    }

    protected function extendFieldsInfo() {

        $fieldsInfo = $this->getFieldsInfo();

        foreach ($fieldsInfo as $key => $value) {


            if (array_key_exists("association", $fieldsInfo[$key]) && ( $fieldsInfo[$key]["association"] == "ManyToOne" || $fieldsInfo[$key]["association"] == "OneToOne" )) {

                if ($fieldsInfo[$key]["association"] == "ManyToMany") {
                    $this->manyToManyRelationExists = true;
                }


                $model = $this->getContainer()->get("model_factory")->getModel($fieldsInfo[$key]["object_name"]);
                if ($model->checkPropertyByName("name")) {
                    $fieldsInfo[$key]["default_field"] = "name";
                    $fieldsInfo[$key]["default_field_type"] = "Text";
                } else {
                    $fieldsInfo[$key]["default_field"] = "id";
                    $fieldsInfo[$key]["default_field_type"] = "Number";
                }
            } elseif (array_key_exists("association", $fieldsInfo[$key]) && ( $fieldsInfo[$key]["association"] == "ManyToMany" || $fieldsInfo[$key]["association"] == "OneToMany" )) {
                unset($fieldsInfo[$key]);
            }
        }

        return $fieldsInfo;
    }

    public function getDefaultField($entityName) {
        $model = $this->getContainer()->get("model_factory")->getModel($entityName);
        if ($model->checkPropertyByName("name")) {
            return "name";
        } else {
            return "id";
        }
    }

}
