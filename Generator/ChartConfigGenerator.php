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
class ChartConfigGenerator extends ClassGenerator {

    protected $fieldName;

    public function __construct($container, $entityName, $templatePath, $fileName, $rootFolder, $prefix = null, $subPrefix = null, $parentEntity = null, $fieldName) {
        parent::__construct($container, $entityName, $templatePath, $fileName, $rootFolder, $prefix, $subPrefix, $parentEntity);
        $this->fieldName = $fieldName;
    }

    protected function getNamespace() {
        
        $arr=explode(DIRECTORY_SEPARATOR,parent::getNamespace());
        unset($arr[count($arr)-1]);
        return implode(DIRECTORY_SEPARATOR,$arr);
        //return  "Config" .DIRECTORY_SEPARATOR. $this->getRootFolder();
    }

    protected function getDirectoryPath() {
        return "Config" . DIRECTORY_SEPARATOR . $this->getRootFolder();
    }

    protected function getTemplateData() {

        $fieldsInfo = $this->getExtendedFieldsInfo();
   
        $templateData = parent::getTemplateData();
        return array_merge($templateData, [
            "field" => $this->fieldName,
            "fieldParam" => $fieldsInfo[$this->fieldName]
              
        ]);
    }

}
