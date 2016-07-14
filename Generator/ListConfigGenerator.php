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
class ListConfigGenerator extends ClassGenerator{

  
    
    
    protected function getTemplateData() {

        $templateData=parent::getTemplateData();
        return array_merge($templateData,
        [
            "lcObjectName" => lcfirst($this->getEntityName()),
            "listConfigNamespaceName" => "@to do",
            "associated" => $templateData["associations"]
        ]);
    }
    
}
