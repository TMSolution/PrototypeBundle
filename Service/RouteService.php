<?php

namespace Core\PrototypeBundle\Service;

class RouteService {

    protected $container;

    public function __construct($container) {
        $this->container = $container;
    }

    
    
    
    public function getLink($route,$parameters,$referenceType=null)
    {
       return $this->container->get('router')->generate($route, $parameters, $referenceType);
    }
    
    
    protected function checkRoutingsExists($config, $name) {
        return $config->has('routings') && array_key_exists($name, $config->get('routings'));
    }

    public function getRouteParams($config, $name,array $routeParams = []) {

        if ($this->checkRoutingsExists($config, $name)) {

            $routings = $config->get('routings');
            $routeConfig = $routings[$name];
            if (array_key_exists("route_params", $routeConfig) && is_array($routeConfig["route_params"])) {

                if(array_key_exists("_resetParams",$routeConfig["route_params"]))
                {
                   return []; 
                }else
                {
                   return array_merge($routeParams, $routeConfig["route_params"]);
                }
                
              
            } else {
                return $routeParams;
            }
        } else {
            return $routeParams;
        }
    }

    public function getRouteName($config, $name) {

        if ($this->checkRoutingsExists($config, $name)) {

            $routings = $config->get('routings');
            $routeConfig = $routings[$name];
            $routeName = $routeConfig["route"];
            $routeName = str_replace('*', $this->getRoutePrefix() . '-', $routeName);
        } else {

            $routeName = $this->getRoutePrefix() . '-' . $name;
        }
        //$router = $this->container->get('router');


        /*
          if (false == $router->getRouteCollection()->get($routeName)) {
          die('Route ' . $routeName . ' doesn\'t exist!');
          throw new \Exception('Route ' . $routeName . ' doesn\'t exist!');
          } */

        return $routeName;
    }

    public function getRoutePrefix() {
  
        $routeStringArray = explode("-", $this->getBaseRouteName());
        $this->routePrefix = implode("-", array_slice($routeStringArray, 0, -1));

        return $this->routePrefix;
    }

    protected function getBaseRouteName() {

        $request = $this->container->get('request_stack')->getCurrentRequest();
        return $request->attributes->get('_route');
    }

}
