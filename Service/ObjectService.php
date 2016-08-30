<?php

namespace Core\PrototypeBundle\Service;


class ObjectService {

    
    protected $container;
    
    public function __construct($container) {

        $this->container=$container;
    }   
 
    public function getEntity($className,$id) {
        
         $model = $this->container->get("model_factory")->getModel($className);
         return $model->findOneById($id); 
         
    }
    
    

}
