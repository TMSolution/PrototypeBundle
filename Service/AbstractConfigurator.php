<?php

namespace Core\PrototypeBundle\Service;

class AbstractConfigurator {

    protected $namesOfServices = [];
    protected $servicesConfigs = [];
    protected $chosen = null;
    protected $chosenService= null;
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
        $servicesConfigs = [];

        foreach ($this->getNamesOfServices() as $serviceName) {
            if (strstr($serviceName, $bestSuitedRouteName)) {
                $servicesConfigs[$serviceName] = $this->servicesConfigs[$serviceName];
            };
        }

        return $servicesConfigs;
    }

    public function getNamesOfServices() {

        if (!$this->namesOfServices) {

            $this->namesOfServices = array_keys($this->servicesConfigs);
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
    
    
    protected function calculateService($servicesConfigs)
    {
            $keys = array_keys($servicesConfigs);
            $serviceName=$keys[0];
            $servisConfig=$servicesConfigs[$serviceName];
            $this->setChosen($servisConfig);
            return $servisConfig["service"];      
    }

    public function getService($route, $entity) {
    
         $bestSuitedServicesConfigs = $this->getBestSuitedServices($this->calculateBestSuitedServiceName($route));

        //there is services for route
        if (count($bestSuitedServicesConfigs)) {

            // check configuration for entity
            $serviceConfigArr = array_filter($bestSuitedServicesConfigs, function($k) use ($route, $entity) {
                if (strstr($k, $entity)) {
                   
                    return $this->servicesConfigs[$k];
                }
            }, ARRAY_FILTER_USE_KEY);

            if (count($serviceConfigArr)) {
                return array_shift($serviceConfigArr["service"]);
            }
            //if there is no configuration for entity, but route exists
            return $this->calculateService($bestSuitedServicesConfigs);
        }
        //there is no service for this route
        else {
            //maybe is service for entity exists
            $serviceConfigArr = array_filter($this->servicesConfigs, function($k) use ($route, $entity) {

                if ($this->divider . $entity == $k) {
                
                    return $this->servicesConfigs[$k];
                }
            }, ARRAY_FILTER_USE_KEY);

            if (count($serviceConfigArr)) {
                return $this->calculateService($serviceConfigArr);
            }
        }

        //use universal config
        if(count($this->servicesConfigs)){
        $this->setChosen($this->servicesConfigs[$this->divider]);
        return $this->servicesConfigs[$this->divider]["service"];
        
        }
    }


    public function __construct($container) {
       
        $this->container = $container;
    }

    public function addService($service, $route, $entity,$id) {
       
        $phrase=$route . $this->divider . $entity;
        $this->servicesConfigs[$route . $this->divider . $entity] =["phrase"=>$phrase, "route"=>$route, "entity"=>$entity, "serviceid"=>$id,"service"=> $service];
    }

}
