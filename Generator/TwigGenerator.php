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

    
    
    protected $viewType;

    public function __construct($container, $entityName, $templatePath, $fileName, $rootFolder, $withAssociated,$viewType) {
    
        parent::__construct($container, $entityName, $templatePath, $fileName, $rootFolder, $withAssociated);
        $this->viewType=$viewType;
        
    }
   
    public function getInstance($entityName,$directory){
    
        return new static($this->getContainer(), $entityName , $this->getTemplatePath(), $this->getFileName(), $directory, FALSE, $this->getViewType());
    }
    

    public function getViewType() {
        return $this->viewType;
    }

   
    protected function getDirectoryPath() {
        return "Resources" . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR . $this->getRootFolder() . DIRECTORY_SEPARATOR . $this->getEntityShortName() . DIRECTORY_SEPARATOR . $this->getViewType();
    }
    
    
    
    protected function getTwigPath()
    {
       $arr=explode("\\Entity\\",$this->getEntityName());
       $bundleName=implode("",explode("\\",$arr[0]));
       return $bundleName.":".$this->getRootFolder()."\\".$this->getEntityShortName()."\\".$this->getViewType()."\\".$this->getFileName();                   
    }
    
    
    public function generate() {
        parent::generate();
        return $this->getTwigPath();
    }
    

}
