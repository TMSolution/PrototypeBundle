<?php

namespace Core\PrototypeBundle\Service;

class RouteService {

    protected $container;

    public function __construct($container) {
        $this->container = $container;
    }

    public function getRouteName($config, $name, $default = "read") {
        $routings = $config->get('routings');
        if ($name && array_key_exists($name, $routings)) {

            $routeConfig = $routings[$name];
            $routeName = $routeConfig["route"];
            $routeName = str_replace('*', $this->getRoutePrefix() . '_', $routeName);
        } else {


            $routeName = $this->getRoutePrefix() . '_' . $default;

            $router = $this->container->get('router');
            if (false == $router->getRouteCollection()->get($routeName)) {
                throw new \Exception('Route ' . $routeName . ' doesn\'t exists!');
            }
        }
        return $routeName;
    }

    public function getRoutePrefix() {
        $routeStringArray = explode("_", $this->getBaseRouteName());
        $this->routePrefix = implode("_", array_slice($routeStringArray, 0, -1));

        return $this->routePrefix;
    }

    protected function getBaseRouteName() {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $request->attributes->get('_route');
        return $request;
    }

}
