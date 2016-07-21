<?php

/**
 * Copyright (c) 2014, TMSolution
 * All rights reserved.
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace Core\PrototypeBundle\Generator\ServiceStrategy;

class SearchConfigStrategy extends DefaultStrategy {

    function getClassNamespace() {
        
        
        return 'Core\\PrototypeBundle\\Config\\SearchConfig';
    }
    
    
    protected function getModel() {
        
        return $this->getContainer()->get("model_factory")->getModel($this->getEntityName());
    }

    public function getFilterParameters() {
        

        $filterParameters = [];
        
        $entityShortName =$this->getModel()->getEntityName(); 
        $fieldsInfo =  $this->getModel()->getFieldsInfo();

        foreach ($fieldsInfo as $key => $value) {

            if (array_key_exists("association", $fieldsInfo[$key]) && ( $fieldsInfo[$key]["association"] == "ManyToOne" || $fieldsInfo[$key]["association"] == "OneToOne" )) {

                $model = $this->getContainer()->get("model_factory")->getModel($fieldsInfo[$key]["object_name"]);
                
                $filterParameters[$key]=[];
                $filterParameters[$key]["id"]=sprintf("_%s.id",$key);
                
                if ($model->checkPropertyByName("name")) {
                    
                    $filterParameters[$key]["name"]=sprintf("_%s.name",$key);
                    
                }
            } elseif (!array_key_exists("association", $fieldsInfo[$key])) {
            
                $filterParameters[$entityShortName][$key] = sprintf("%s.%s",$entityShortName,$key);
            }
        }
        
        return $filterParameters;
    }

    function getArguments() {
        return array_merge($this->arguments, [$this->getFilterParameters()]);
    }

    protected function getConfigParameterName() {

        return sprintf("%s.%s", $this->getServiceName(), "parameters");
    }

}
