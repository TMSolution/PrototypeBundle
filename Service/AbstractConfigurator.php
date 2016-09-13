<?php

namespace Core\PrototypeBundle\Service;

class AbstractConfigurator {

    protected $namesOfServices = [];
    protected $servicesConfigs = [];
    protected $chosen = null;
    protected $chosenService = null;
    protected $divider = "*";
    protected $strict;

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

    

    protected function findByBestSuitedRoute($route, $serviceConfigs) {


        $bestServiceConfig = null;
        $bestScore = 0;

        foreach ($serviceConfigs as $serviceConfig) {

            $serviceConfigRoute = $serviceConfig['route'];
            if ($serviceConfigRoute) {
                if (\mb_substr($route, 0, mb_strlen($serviceConfigRoute)) == $serviceConfigRoute) {
                    $score = similar_text($route, $serviceConfigRoute);
                    if ($score >= $bestScore) {
                        $bestScore = $score;
                        $bestServiceConfig = $serviceConfig;
                    }
                }
            }
        }

        return $bestServiceConfig;
    }

    protected function compare($params, $tags) {
        
  
        return count(array_intersect($params, $tags));
    }

    protected function findServiceConfig($params) {

        $numberOfParams = count($params);

        $bestScore = null;
        foreach ($this->servicesConfigs as $serviceConfig) {

            $score = $this->compare($params, $serviceConfig['tags']);
            if ($score == $numberOfParams) {
                return $serviceConfig;
            }
        }

        if ($this->dev) {
            
            return $this->defualtServiceConfig;
        } else {


            throw new \Exception('Service  not match');
        }
    }

    public function getService($route, $entity, $parentEntity = null, $prefix = null) {

        $params = [];
        //$params['route'] = $route;

        $params['entity'] = $entity;
        $params['parentEntity'] = $parentEntity;
        $params['prefix'] = $prefix;

     
        $serviceConfig = $this->findServiceConfig($params);
       
        if ($serviceConfig) {
            $this->setChosen($serviceConfig);
            return $serviceConfig["service"];
        }
        else 
        {
            throw new \Exception('Service for routing not match');
        }
    }

    public function __construct($container, $dev = true) {

        $this->container = $container;
        $this->dev = $dev;
    }

    public function addService($service, $route, $entity, $id, $parentEntity = null, $prefix = null) {

        $phrase = $route . $this->divider . $entity . $this->divider . $parentEntity . $this->divider . $prefix ;
        $this->servicesConfigs[$phrase] = ["phrase" => $phrase, "route" => $route, "tags" => [ "entity" => $entity, 'parentEntity' => $parentEntity, 'prefix' => $prefix], "serviceid" => $id, "service" => $service];

        if (!$route and ! $entity and ! $parentEntity and ! $prefix ) {
            $this->defualtServiceConfig = $this->servicesConfigs[$phrase];
        }
    }

}
