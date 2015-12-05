<?php

namespace Core\PrototypeBundle\Config;

/**
 * Description of ListConfig
 *
 * @author Mariusz
 */
class ViewConfig {


    protected $model;
    protected $container;
   
    public function __construct($container)
    {
        $this->container=$container;
    }
    
    protected function getContainer()
    {
        return $this->container;
    }
    
    public function setModel($model)
    {
        $this->model=$model;
       
    }
    
    public function getView($options)
    {
        return $options;
    }
    
}
