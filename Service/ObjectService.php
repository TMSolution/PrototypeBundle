<?php

namespace Core\PrototypeBundle\Service;


class ObjectService {

    
    protected $container;
    
    public function __construct($container) {

        $this->container=$container;
    }   
 
    protected function getParent($entity,$methods,&$parents) {
        
        $method = array_shift($methods);
        $parents[]=$entity=$entity->$method();
        
        if (count($methods) == 0) {

            return null;
        }
        else
        {
           return $this->getParent($entity->$method(),$methods,$parents);
        }
    }
    
    public function getParents($entity,$methods) {
       $parents=[];
       $this->getParent($entity,$methods,$parents);
       return array_reverse($parents);
    }
    
    
    

}
