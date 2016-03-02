<?php

namespace Core\PrototypeBundle\Profiler;

use AppKernel;
use Exception;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Core\PrototypeBundle\Command\ShowUrlConfigurationCommand;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;

class PrototypeCollector implements DataCollectorInterface {

    private $data = [];
    protected $container;
    protected $servicesInfo = [];
    protected $services = [
        'prototype.configurator.service' => 'Base Config',
        'prototype.gridconfig.configurator.service' => 'Grid Config',
        'prototype.listconfig.configurator.service' => 'List Config',
        'prototype.viewconfig.configurator.service' => 'View Config',
        'prototype.formtype.configurator.service' => 'Form Type Config'
    ];
    protected $actionMap = [
        'new' => 'create',
        'create' => 'create',
        'read' => 'read',
        'view' => 'view',
        'update' => 'update',
        'edit' => 'update',
        'list' => 'list',
        'grid' => 'grid',
        'error' => 'error'
    ];
    protected $usedConfigParams = [];

    protected function calculateAction($actionPath) {

        $actionArr = explode(':', $actionPath);
        $action = substr(end($actionArr), 0, -6);

        if (isset($this->actionMap[$action])) {
            return $this->actionMap[$action];
        } else {
            throw new \Exception('Action ' . $action . ' configuration doesn\'t exist!');
        }
    }

    protected function getServiceInfo($name, $configuratorService) {

        $serviceInfo = new \stdClass();
        $serviceInfo->name = $name;
        $serviceInfo->params = $configuratorService->getChosen();
        if (isset($serviceInfo->params['service'])) {
            $serviceInfo->className = get_class($serviceInfo->params['service']);
        } else {
            $serviceInfo->className = null;
        }
        return $serviceInfo;
    }

    protected function getConfiguratorService($serviceName) {
        $configuratorService = $this->container->get($serviceName);
        $namesOfServices = $configuratorService->getNamesOfServices();
        $configuratorService->getService($this->data["route"], $this->data["entityClass"], $this->data["parentEntityClass"], $this->data["actionId"]);
        return $configuratorService;
    }

    protected function configureServices() {

        $services = [];
        foreach ($this->services as $serviceName => $friendlyName) {

            $services[$friendlyName] = $this->getServiceInfo($friendlyName, $this->getConfiguratorService($serviceName));
        }

        return $services;
    }

    protected function prepareConfig() {

        $baseConfig = $this->data["services"]["Base Config"];
        $configService = $baseConfig->params["service"];
        $resulConfig = [];
        $devConfig = new \Core\PrototypeBundle\Service\DevConfig($configService);
        $config = $devConfig->getConfigWithDifferences();
        $this->prepareUsedParams($config);
        $this->processConfig($config, $resulConfig);
        $this->data['config'] = $resulConfig;
        $this->data['overriden'] = $devConfig->isOverriden();
    }

    public function prepareUsedParams($config) {
        $result = [];
        $configAction = $this->actionMap[$this->data["actionName"]];
        $this->calculateUsedParams('actions.' . $configAction, $config['actions'][$configAction], $result);
        $this->calculateUsedParams('base', $config['base'], $result);
        $this->usedConfigParams = array_merge($result, ["base", "actions", "actions." . $this->data["actionName"]]);
    }

    public function calculateUsedParams($basePath, $array, &$result) {
        foreach ($array as $key => $element) {

            $newPath = $basePath . '.' . $key;
            $result[] = $newPath;
            if (is_array($element)) {

                $this->calculateUsedParams($newPath, $element, $result);
            }
        }
    }

    public function collect(Request $request, Response $response, Exception $exception = null) {

        $kernel = new \AppKernel('dev', true);
        $kernel->boot();
        $this->container = $kernel->getContainer();
        $router = $this->container->get('router');
        $classmapper = $this->container->get('classmapperservice');

        $this->data["header"] = "Url configuration";
        $this->data["uri"] = $request->getPathInfo();
        if ($this->data["uri"] == "/login_check") {
            return;
        }

        try {

            $route = $router->match($this->data["uri"]);

            if (!array_key_exists("_route", $route) || !array_key_exists("_controller", $route) || !array_key_exists("entityName", $route)) {
                return;
            }
            $this->data["route"] = $route["_route"];
            $this->data["controller"] = $route["_controller"];
            $actionName = $this->requestedAction($route["_controller"]);

            $this->data["actionName"] = $actionName;



            if (array_key_exists("_locale", $route)) {

                $this->data["locale"] = $route["_locale"];
                $this->data["entityName"] = $route["entityName"];
                $this->data["parentName"] = array_key_exists("parentName", $route) ? $route["parentName"] : null;
                $this->data["actionId"] = array_key_exists("actionId", $route) ? $route["actionId"] : null;
                $this->data["entityClass"] = $classmapper->getEntityClass($route["entityName"], $this->data["locale"]);

                if ($this->data["parentName"]) {
                    $this->data["parentEntityClass"] = $classmapper->getEntityClass($this->data["parentName"], $this->data["locale"]);
                } else {
                    $this->data["parentEntityClass"] = null;
                }

                $this->data["services"] = $this->configureServices();
                $this->prepareConfig();
            }
        } catch (Symfony\Component\Routing\Exception\ResourceNotFoundException $e) {
            
        }
    }

    public function getData() {
        return $this->data;
    }

    public function getName() {
        return "prototype";
    }

    protected function processConfig(array $paramsArray, &$resultArr, &$n = 0, $path = '') {

        $volume = 0;


        foreach ($paramsArray as $name => $value) {
            $obj = new \stdClass();
            $obj->name = $name;
            $obj->volume = 0;
            $resultArr[$n][] = $obj;

            if ($path) {
                $newPath = $path . '.' . $name;
            } else {
                $newPath = $name;
            }

            if (in_array($newPath, $this->usedConfigParams)) {
                $obj->used = true;
            } else {

                $obj->used = false;
            }

            if (is_array($value)) {
                $obj->volume = $this->processConfig($value, $resultArr, $n, $newPath);
                $obj->value = null;
                if ($obj->volume == 0) {
                    $obj->volume = 1;
                }
                $volume+=$obj->volume;
            } else {

                if (is_bool($value)) {
                    $obj->value = $value ? 'true' : 'false';
                } else {
                    $obj->value = $value;
                }
                $volume++;
            }
            $n++;
        }

        return $volume;
    }

    public function requestedAction($controllerName) {
        $controllerNameArray = explode(":", $controllerName);
        $actionName = substr(end($controllerNameArray), 0, -6);

        return $actionName;
    }

}
