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

class PrototypeCollector implements DataCollectorInterface
{

    private $data = [];
    private $twigYellowChanges = 0;
    private $twigChanges = 0;
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

    public function __construct()
    {
        
    }

    protected function calculateAction($actionPath)
    {

        $actionArr = explode(':', $actionPath);
        $action = substr(end($actionArr), 0, -6);

        if (isset($this->actionMap[$action])) {
            return $this->actionMap[$action];
        } else {
            throw new \Exception('Action ' . $action . ' configuration doesn\'t exist!');
        }
    }

    public function collect(Request $request, Response $response, Exception $exception = null)
    {

        $kernel = new \AppKernel('dev', true);
        $kernel->boot();
        $container = $kernel->getContainer();
        $router = $container->get('router');
        $classmapper = $container->get('classmapperservice');
        $data = [];
        $data["header"] = "Url configuration";
        $data["uri"] = $request->getPathInfo();
        if ($data["uri"] == "/login_check") {
            return;
        }

        try {

            $route = $router->match($data["uri"]);

            if (!array_key_exists("_route", $route) || !array_key_exists("_controller", $route) || !array_key_exists("entityName", $route)) {
                return;
            }
            $data["route"] = $route["_route"];
            $data["controller"] = $route["_controller"];







            if (array_key_exists("_locale", $route)) {

                $this->twigYellowChanges = 0;
                $this->twigChanges = 0;

                $data["locale"] = $route["_locale"];

                $data["entityName"] = $route["entityName"];

                $data["parentName"] = array_key_exists("parentName", $route) ? $route["parentName"] : null;
                $data["actionId"] = array_key_exists("actionId", $route) ? $route["actionId"] : null;
                $data["entityClass"] = $classmapper->getEntityClass($route["entityName"], $data["locale"]);

                if ($data["parentName"]) {
                    $data["parentEntityClass"] = $classmapper->getEntityClass($data["parentName"], $data["locale"]);
                } else {
                    $data["parentEntityClass"] = null;
                }
                $configuratorService = $container->get('prototype.configurator.service');
                $namesOfServices = $configuratorService->getNamesOfServices();
                $service = $configuratorService->getService($data["route"], $data["entityClass"], $data["parentEntityClass"], $data["actionId"]);

                $config = $service->getConfig();

                $data['config'] = $this->printServiceInfo('Config', $configuratorService);


                $data['twigs'] = $this->printConfig($config, $data["controller"]);

                $configuratorService = $container->get('prototype.gridconfig.configurator.service');
                $namesOfServices = $configuratorService->getNamesOfServices();
                $configuratorService->getService($data["route"], $data["entityClass"], $data["parentEntityClass"], $data["actionId"]);
                $data['gridconfig'] = $this->printServiceInfo('Grid', $configuratorService);

                $configuratorService = $container->get('prototype.listconfig.configurator.service');
                $namesOfServices = $configuratorService->getNamesOfServices();
                $configuratorService->getService($data["route"], $data["entityClass"], $data["parentEntityClass"], $data["actionId"]);
                $data['listconfig'] = $this->printServiceInfo('List', $configuratorService);

                $configuratorService = $container->get('prototype.viewconfig.configurator.service');
                $namesOfServices = $configuratorService->getNamesOfServices();
                $configuratorService->getService($data["route"], $data["entityClass"], $data["parentEntityClass"], $data["actionId"]);
                $data['viewconfig'] = $this->printServiceInfo('View', $configuratorService);

                $configuratorService = $container->get('prototype.formtype.configurator.service');
                $namesOfServices = $configuratorService->getNamesOfServices();
                $configuratorService->getService($data["route"], $data["entityClass"], $data["parentEntityClass"], $data["actionId"]);
                $data['formtype'] = $this->printServiceInfo('Form Type', $configuratorService);
            }
        } catch (Symfony\Component\Routing\Exception\ResourceNotFoundException $e) {
            
        }
        $data['twigChanges'] = $this->twigChanges;
        $data['twigYellowChanges'] = $this->twigYellowChanges;
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getName()
    {
        return "prototype";
    }

    protected function renderTemplates($name, $path, $actionName, &$output)
    {
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

    protected function printConfig($config, $controllerName)
    {

        $currentActionName = $this->calculateAction($controllerName);
        $output = [];


        $this->printBase($config['base'], $output);

        $this->printActions($config['actions'], $currentActionName, $output);



        return $output;
    }

    protected function printBase($config, &$output)
    {

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

    protected function printValue($value, $result = [])
    {
        if (is_array($value)) {
            foreach ($value as $partParam => $partValue) {

                $result[] = $partParam;
                $result=array_merge($result,$this->printValue($partValue));
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

    protected function printParameters($actionName, $parameters, &$output)
    {
        foreach ($parameters as $parameter => $value) {
            
        $result='';
$resultArr=[];
            
            $resultArr = $this->printValue($value);
            
            if(count($resultArr)>1)
            {
                $rowspan=2;
            }
            
            $result='<td rowspan="'.$rowspan.'">'.$parameter.'</td>';
            $checker=true;
            foreach ($resultArr as $value){
                
                
                $result.='<td>'.$value.'</td>';
               
                if($checker==false)
                {
                    $result.='</tr><tr>';
                    
                }
                
                $checker=!$checker; 
                
            }
            
            $output[]='<tr>'.$result.'</tr>';
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





function printActions($config, $currentActionName, &$output)
{

    foreach ($config as $actionName => $parameters) {

        $used = ($currentActionName == $actionName) ? ' - used' : NULL;
        $output[] = "<br/><h2>" . strtoupper($actionName) . ' ' . $used . "</h2>";
        $output[] = "<table><thead><th>Parameters</th><th>Values</th></tr></thead><tbody>";
        $this->printParameters($actionName, $parameters, $output);
        $output[] = "</tbody></table>";
    }
    return $output;
    }

    protected

    function printServiceInfo($name, $configuratorService)
    {
        $output = [];


        $serviceArray = $configuratorService->getChosen();
        if ($name) {
            $output[] = "<br/><h2>" . $name . "</h2>";
        }

        $output[] = "<table><thead><tr><th>Name</th><th>Value</th></tr></thead><tbody>";

        if ($serviceArray['phrase']) {
            $output[] = ("<tr><th><strong>phrase</strong></th><td>" . $serviceArray['phrase'] . "</td></tr>");
        }

        if ($serviceArray['serviceid']) {
            $output[] = ("<tr><th><strong>servicename:</strong></th><td>" . $serviceArray['serviceid'] . "</td></tr>");
        } else {
            $output[] = ("<tr><th><strong>servicename:</strong></th><td>none</td></tr>");
        }

        if ($serviceArray['service']) {
            $output[] = ("<tr><th><strong>class</strong></th><td>" . get_class($serviceArray['service']) . "</td></tr>");
        } else {
            $output[] = ("<tr><th><strong>class</strong></th><td>none</td></tr>");
        }
        $output[] = "</tbody></table>";
        return $output;
    }

}
