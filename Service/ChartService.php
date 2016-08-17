<?php

namespace Core\PrototypeBundle\Service;

class ChartService {

    protected $container;

    public function __construct($container) {
        $this->container = $container;
    }

    public function getChart($name)
    {
        if($this->container->has($name))
        {
           return $this->container->get($name)->getChart();
        } 
        else
        {
            throw new \Exception(sprintf("Chart service %s",$name));
        }    
    }
    

}
