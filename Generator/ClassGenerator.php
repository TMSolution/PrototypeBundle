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


    protected function getNamespace() {

        $directory = "Config\\" . $this->getRootFolder();
        $entityNameArr = explode("\\", str_replace("Entity", $directory, $this->getEntityName()));
        return implode("\\", $entityNameArr);
    }

    
    
    
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
