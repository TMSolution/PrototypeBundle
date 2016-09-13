<?php

/**
 * Copyright (c) 2014, TMSolution
 * All rights reserved.
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace Core\PrototypeBundle\Generator;

/**
 * GridConfigCommand generates widget class and his template.
 * @author Mariusz Piela <mariuszpiela@gmail.com>
 * 
 */
class TwigGenerator extends AbstractGenerator {

    
    
    protected $additionalParams=[];
    protected $viewType;

    public function __construct($container, $entityName, $templatePath, $fileName, $rootFolder, $prefix = null,  $parentEntity = null, $viewType, $additionalParams=[]) {
    
        parent::__construct($container, $entityName, $templatePath, $fileName, $rootFolder, $prefix, $parentEntity);
        $this->viewType=$viewType;
        $this->additionalParams=$additionalParams;
    }
   
    
    
    public function getAddtionalParams()
    {
        return $this->additionalParams;
    }
    

    public function getViewType() {
        return $this->viewType;
    }

   
    protected function getDirectoryPath() {
        return "Resources" . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR . $this->getRootFolder() . DIRECTORY_SEPARATOR . $this->getEntityShortName() . DIRECTORY_SEPARATOR . $this->getViewType();
    }
    
    
    protected function getTemplateData() {
    
      
        return array_merge(parent::getTemplateData(),$this->getAddtionalParams());
    }
    
    protected function getTwigPath()
    {
       $arr=explode("\\Entity\\",$this->getEntityName());
       $bundleName=implode("",explode("\\",$arr[0]));
       return $bundleName.":".$this->getRootFolder()."\\".$this->getEntityShortName()."\\".$this->getViewType().":".$this->getFileName();                   
    }
    
    
    public function generate() {
        parent::generate();
        return $this->getTwigPath();
    }
    

}
