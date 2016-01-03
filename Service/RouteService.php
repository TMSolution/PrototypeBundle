<?php

namespace Core\PrototypeBundle\Service;

class RouteService {

    protected $container;

    public function __construct($container) {
        $this->container = $container;
    }

    protected function checkRoutingsExists($config, $name) {
        return $config->has('routings') && array_key_exists($name, $config->get('routings'));
    }

    public function getRouteParams($config, $name,array $routeParams = []) {

        if ($this->checkRoutingsExists($config, $name)) {

            $routings = $config->get('routings');
            $routeConfig = $routings[$name];
            if (array_key_exists("route_params", $routeConfig) && is_array($routeConfig["route_params"])) {

                return array_merge($routeParams, $routeConfig["route_params"]);
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
            $routeName = str_replace('*', $this->getRoutePrefix() . '_', $routeName);
        } else {

            $routeName = $this->getRoutePrefix() . '_' . $name;
        }
        $router = $this->container->get('router');


        /*
          if (false == $router->getRouteCollection()->get($routeName)) {
          die('Route ' . $routeName . ' doesn\'t exists!');
          throw new \Exception('Route ' . $routeName . ' doesn\'t exists!');
          } */

        return $routeName;
    }

    public function getRoutePrefix() {
        $routeStringArray = explode("_", $this->getBaseRouteName());
        $this->routePrefix = implode("_", array_slice($routeStringArray, 0, -1));

        return $this->routePrefix;
    }

    protected function getBaseRouteName() {

        $request = $this->container->get('request_stack')->getCurrentRequest();
        return $request->attributes->get('_route');
    }

}
