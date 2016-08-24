<?php

/**
 * Copyright (c) 2014, TMSolution
 * All rights reserved.
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace Core\PrototypeBundle\Generator\ServiceStrategy;


class ChartStrategy extends DefaultStrategy {

 
    public  function getTags() {

        $arguments = ['name' => "{$this->tags[0]["name"]}"];
        return [$arguments];
    }
    
    public function getServiceName()
    {
        $arr=explode(".",$this->serviceName);
        //unset parent entity name
        unset($arr[count($arr)-2]);
        return implode(".",$arr);
    }
    
    
    public function getClassNamespace()
    {
     
        $arr=explode("\\",$this->classNamespace);
        //unset parent entity name
        unset($arr[count($arr)-2]);
       
        return implode("\\",$arr);
    }
    
    
    
}
