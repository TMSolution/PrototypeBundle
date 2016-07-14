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
class FormTypeGenerator extends ClassGenerator{
 
     protected $types = [
        "string" => "text",
        "text" => "text",
        "blob" => "text",
        "integer" => "number",
        "smallint" => "number",
        "bigint" => "number",
        "decimal" => "number",
        "float" => "number",
        "duble" => "number",
        "boolean" => "boolean",
        "datetime" => "date",
        "datetimetz" => "date",
        "date" => "date",
        "time" => "text",
        "array" => "array",
        "simple_array" => "array",
        "json_array" => "array",
        "object" => "entity"
    ];
     
     
   
    
    
    protected function getTemplateData() {

        $templateData=parent::getTemplateData();
        
        $formTypeNamespaceName = $this->getNamespace();
        $formTypeName = strtolower(str_replace('\\', '_', $this->getEntityName()) . '_' . $this->getEntityShortName());
    
        $fieldsInfo=$this->getFieldsInfo();
        
        foreach ($fieldsInfo as $key => $field) {
            $fieldsInfo[$key]['formType'] = $this->types[$field['type']];
        }
        
        return array_merge($templateData,
        [
            "formTypeNamespace" => $formTypeNamespaceName,
            "formTypeName" => $formTypeName,
            "fieldsInfo" => $fieldsInfo
 
        ]);
    }
    

    
}
