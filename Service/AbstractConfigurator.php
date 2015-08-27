<?php

namespace Core\PrototypeBundle\Service;

class AbstractConfigurator {

    protected $namesOfServices = [];
    protected $services = [];
    protected $chosen = null;
    protected $divider = "*";

    protected function getRouteName($serviceName) {
        $serviceNameArr = explode($this->divider, $serviceName);
        return $serviceNameArr[0];
    }

    protected function calculateBestSuitedServiceName($route) {

        $bestSuitedRouteName = null;
        $bestScore = 0;

        $namesOfServices = $this->getNamesOfServices();
        foreach ($namesOfServices as $serviceName) {

            $routeName = $this->getRouteName($serviceName);
            if ($routeName) {
                if (\mb_substr($route, 0, mb_strlen($routeName)) == $routeName) {
                    $score = similar_text($route, $routeName);
                    if ($score > $bestScore) {
                        $bestScore = $score;
                        $bestSuitedRouteName = $routeName;
                    }
                }
            }
        }

        return $bestSuitedRouteName;
    }

    protected function getBestSuitedServices($bestSuitedRouteName) {
        $services = [];

        foreach ($this->getNamesOfServices() as $serviceName) {
            if (strstr($serviceName, $bestSuitedRouteName)) {
                $services[$serviceName] = $this->services[$serviceName];
            };
        }

        return $services;
    }

    public function getNamesOfServices() {

        if (!$this->namesOfServices) {

            $this->namesOfServices = array_keys($this->services);
        }
        return $this->namesOfServices;
    }

    public function setChosen($chosen) {

        $this->chosen = $chosen;
    }

    public function getChosen() {
        if (!$this->chosen) {
            //  throw new \Exception("Chosen value was not set, becouse getService method was not use!");
        }
        return $this->chosen;
    }

    public function getService($route, $entity) {
        dump(get_class($this));
        dump($this->services);
        //get best fit route
        $bestSuitedServices = $this->getBestSuitedServices($this->calculateBestSuitedServiceName($route));

        //there is services for route
        if (count($bestSuitedServices)) {

            // check configuration for entity
            $serviceArr = array_filter($bestSuitedServices, function($k) use ($route, $entity) {
                if (strstr($k, $entity)) {
                    $this->setChosen($k);
                    return $this->services[$k];
                }
            }, ARRAY_FILTER_USE_KEY);

            if (count($serviceArr)) {
                return array_shift($serviceArr);
            }
            //if there is no configuration for entity, but route exists
            $keys = array_keys($bestSuitedServices);
            $this->setChosen($keys[0]);
            return $bestSuitedServices[$keys[0]];
        }
        //there is no service for this route
        else {
            //maybe is service for entity exists
            $serviceArr = array_filter($this->services, function($k) use ($route, $entity) {

                if ($this->divider . $entity == $k) {
                    $this->setChosen($k);
                    return $this->services[$k];
                }
            }, ARRAY_FILTER_USE_KEY);

            if (count($serviceArr)) {
                $keys = array_keys($serviceArr);
                $this->setChosen($keys[0]);
                return $serviceArr[$keys[0]];
            }
        }

        //use universal config
       
        if(count($this->services)){
        $this->setChosen($this->divider);
     
        return $this->services[$this->divider];
        
        }
    }


    public function __construct($container) {
       
        $this->container = $container;
    }

    public function addService($service, $route, $entity) {
       
        $this->services[$route . $this->divider . $entity] = $service;
    }

}
