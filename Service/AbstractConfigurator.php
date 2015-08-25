<?php

namespace Core\PrototypeBundle\Service;

class AbstractConfigurator {

    protected $services = [];

    public function getService($route, $entity) {

       
        //for most precise config
        $service = null;



        if (array_key_exists($route . '.' . $entity, $this->services)) {

            $service = $this->services[$route . '.' . $entity];
        }

        //for route based config
        if (!$service) {

          
            $serviceArr = array_filter($this->services, function($k) use ($route, $entity) {

                if (strstr($route . '.', $k)) {
                    return $this->services[$k];
                }
            }, ARRAY_FILTER_USE_KEY);
           
       
            $service = array_shift($serviceArr);
        }

        //for entity based config
        if (!$service) {

            $serviceArr = array_filter($this->services, function($k) use ($route, $entity) {

                if (strstr('.' . $entity, $k)) {
                    return $this->services[$k];
                }
            }, ARRAY_FILTER_USE_KEY);
            $service = array_shift($serviceArr);
        }

        // default
        if (!$service) {
            if (array_key_exists('.', $this->services)) {

                $service = $this->services['.'];
            }
        }
        

        return $service;

        
    }

    public function __construct($container) {
        $this->container = $container;
    }

    public function addService($service, $route, $entity) {

        $this->services[$route . '.' . $entity] = $service;
    }

}
