<?php

namespace Core\PrototypeBundle\Service;

class AbstractConfigurator {

    protected $namesOfServices = [];
    protected $servicesConfigs = [];
    protected $chosen = null;
    protected $chosenService= null;
    protected $divider = "*";
    
    

    
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
    
   
    
    
    protected function findByBestSuitedRoute($route,$serviceConfigs)
    {
        
        $bestServiceConfig = null;
        $bestScore = 0;

        foreach ($serviceConfigs as $serviceConfig) {

            $serviceConfigRoute = $serviceConfig['route'];
            if ($serviceConfigRoute) {
                if (\mb_substr($route, 0, mb_strlen($serviceConfigRoute)) == $serviceConfigRoute) {
                    $score = similar_text($route, $serviceConfigRoute);
                    if ($score > $bestScore) {
                        $bestScore = $score;
                        $bestServiceConfig = $serviceConfig;
                    }
                }
            }
        }
        
        return $bestServiceConfig;
        
    }
    
    
    public function getService($route, $entity,$actionId='default') {
        
        $forEntity=[];
        $universal=[];
        $serviceConfig=null;
        
      
        foreach($this->servicesConfigs as $serviceConfig)
        {
            if(!$serviceConfig['actionId'] || $serviceConfig['actionId']='default')
            {
                
                
            
            
            if($serviceConfig['entity']==$entity )
            {
              $forEntity[]=$serviceConfig;  
            }
            elseif(!$serviceConfig['entity']){
                 
                $universal[]=$serviceConfig;
            }
            }
        }
        
        
        
        
        
        
        if(count($forEntity)>0)
        {
            $serviceConfig=$this->findByBestSuitedRoute($route,$forEntity);
        }
        
        if(!$serviceConfig)
        {
            $serviceConfig=$this->findByBestSuitedRoute($route,$universal);
        }
        if(!$serviceConfig)
        {
            if(array_key_exists($this->divider,$this->servicesConfigs)){
            $serviceConfig=$this->servicesConfigs[$this->divider];}
        }
        if($serviceConfig){
        $this->setChosen($serviceConfig);
        return $serviceConfig["service"];
        }
    }
   

    public function __construct($container) {
       
        $this->container = $container;
    }

    public function addService($service, $route, $entity, $actionId,$id) {
       
        $phrase=$route . $this->divider . $entity;
        $this->servicesConfigs[$route . $this->divider . $entity] =["phrase"=>$phrase, "route"=>$route, "entity"=>$entity, "serviceid"=>$id, "actionId"=>$actionId, "service"=> $service];
    }

}
