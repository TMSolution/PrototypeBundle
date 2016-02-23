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
    private $twigYellowChanges = 0;
    private $twigChanges = 0;
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

    public function __construct() {
        
    }

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


            if (array_key_exists("_locale", $route)) {

                $this->twigYellowChanges = 0;
                $this->twigChanges = 0;

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
                $baseConfig = $this->data["services"]["Base Config"];
                $configService = $baseConfig->params["service"];
                $resulConfig = [];
                $this->prepareConfig($configService->getConfig(), $resulConfig);

                $this->data['config'] = $resulConfig;
            }
        } catch (Symfony\Component\Routing\Exception\ResourceNotFoundException $e) {
            
        }


        $this->data['twigChanges'] = $this->twigChanges;
        $this->data['twigYellowChanges'] = $this->twigYellowChanges;
        $this->data = $this->data;
    }

    public function getData() {
        return $this->data;
    }

    public function getName() {
        return "prototype";
    }

    protected function prepareConfig(array $paramsArray, &$resultArr, &$n = 0) {

        $volume = 0;
        
//        if(!$n){
//            $n=0;
//        }
        
        foreach ($paramsArray as $name => $value) {
            $obj = new \stdClass();
            $obj->name = $name;
            $obj->volume = 0;
            $resultArr[$n][]=$obj;

            if (is_array($value)) {
                $obj->volume = $this->prepareConfig($value,$resultArr, $n);
                $obj->value = null;
                if($obj->volume==0)
                {$obj->volume=1;}
                $volume+=$obj->volume;
            } else {
                
                if(is_bool($value))
                {
                   $obj->value=$value?'true':'false';
                }
                else
                {    
                    $obj->value = $value;
                }
                $volume++;
            }
            $n++;
        }

        return $volume;
    }

    /*

      protected function renderTemplates($name, $path, $actionName, &$output) {
      if (!is_array($path)) {
      if (strstr($path, 'CorePrototypeBundle:Default')) {
      $output[] = ("<tr><th>$name</th><td>$path</td></tr>");
      } elseif (strstr($path, 'Default')) {
      $this->twigYellowChanges++;
      $output[] = ("<tr><th>$name</th><td><span style='background-color: #aacd4e; border-radius: 6px; color: #fff; display: inline-block;margin-right: 2px;padding: 4px;'>$path</span></td></tr>");
      } else {
      $this->twigChanges++;
      $output[] = ("<tr><th>$name</th><td><span style='background-color: #ffcc00; border-radius: 6px; color: #000000; display: inline-block;margin-right: 2px;padding: 4px;'>$path</span></td></tr>");
      }
      }
      }

      protected function printConfig($config, $controllerName) {

      $currentActionName = $this->calculateAction($controllerName);
      $output = [];


      $this->printBase($config['base'], $output);

      $this->printActions($config['actions'], $currentActionName, $output);



      return $output;
      }

      protected function printBase($config, &$output) {

      if (isset($config['templates'])) {
      $output[] = "<br/><h2>Base twigs</h2>";
      $output[] = "<table><thead><tr><th>Type</th><th>Twig</th></tr></thead><tbody>";

      if (isset($config['templates'])) {
      foreach ($config['templates'] as $name => $path) {
      $this->renderTemplates($name, $path, 'base', $output);
      }
      }
      $output[] = "</tbody></table>";
      }
      }

      protected function printValue($value, $result = []) {
      if (is_array($value)) {
      foreach ($value as $partParam => $partValue) {

      $result[] = $partParam;
      $result = array_merge($result, $this->printValue($partValue));
      }
      } elseif (is_bool($value)) {
      if ($value === true) {
      $result[] = 'true';
      } else {
      $result[] = 'false';
      }
      } else {
      $result[] = $value;
      }

      return $result;
      }

      protected function printParameters($actionName, $parameters, &$output) {
      foreach ($parameters as $parameter => $value) {

      $result = '';
      $resultArr = [];

      $resultArr = $this->printValue($value);

      if (count($resultArr) > 1) {
      $rowspan = 2;
      }

      $result = '<td rowspan="' . $rowspan . '">' . $parameter . '</td>';
      $checker = true;
      foreach ($resultArr as $value) {


      $result.='<td>' . $value . '</td>';

      if ($checker == false) {
      $result.='</tr><tr>';
      }

      $checker = !$checker;
      }

      $output[] = '<tr>' . $result . '</tr>';
      }



      //            if (isset($parameters['templates'])) {
      //                foreach ($parameters['templates'] as $name => $path) {
      //
      //                    $this->renderTemplates($name, $path, $actionName, $output);
      //                }
      //            }else
      //            {
      //                  $output[]="<tr><td colspan=''>".(string) $parameter."</td></tr>";
      //
      //            }
      }

      function printActions($config, $currentActionName, &$output) {

      foreach ($config as $actionName => $parameters) {

      $used = ($currentActionName == $actionName) ? ' - used' : NULL;
      $output[] = "<br/><h2>" . strtoupper($actionName) . ' ' . $used . "</h2>";
      $output[] = "<table><thead><th>Parameters</th><th>Values</th></tr></thead><tbody>";
      $this->printParameters($actionName, $parameters, $output);
      $output[] = "</tbody></table>";
      }
      return $output;
      }
     */
}
