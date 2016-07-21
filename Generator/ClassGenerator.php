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
abstract class ClassGenerator extends AbstractGenerator {

//    protected $filePath;
//
//    public function __construct($container, $entityName, $templatePath, $fileName, $rootFolder, $withAssociated, $filePath) {
//
//        parent::__construct($container, $entityName, $templatePath, $fileName, $rootFolder, $withAssociated);
//        $this->filePath = $filePath;
//    }

    protected function getNamespace() {

        $directory = "Config\\" . $this->getRootFolder();
//        if ($this->getFilePath()) {
//            $directory = str_replace(DIRECTORY_SEPARATOR, "\\", "Config\\" . $this->getRootFolder() . "\\" . $this->getFilePath());
//        }
        $entityNameArr = explode("\\", str_replace("Entity", $directory, $this->getEntityName()));
        //  unset($entityNameArr[count($entityNameArr) - 1]);
        return implode("\\", $entityNameArr);
    }
//
//    public function setFilePath($filePath) {
//        $this->filePath = $filePath;
//    }
//
//    public function getFilePath() {
//        return $this->filePath;
//    }
    
    
    
    protected function getClassName()
    {
        $arr= explode(".",$this->getFileName());
        return $arr[0]; 
    }
    

    protected function getDirectoryPath() {
        return "Config" . DIRECTORY_SEPARATOR . $this->getRootFolder() . DIRECTORY_SEPARATOR . $this->getEntityShortName();
    }

    protected function getTemplateData() {

        $templateData = parent::getTemplateData();
        return array_merge($templateData, [
            "namespace" => $this->getNamespace(),
            "className"=>  $this->getClassName()   
        ]);
    }

}
