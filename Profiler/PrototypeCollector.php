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

/**
 * @author Krzysiek Piasecki <krzysiekpiasecki@gmail.com>
 */
class PrototypeCollector implements DataCollectorInterface
{

    private $data = [];
    private $twigChanges = 0;

    public function __construct()
    {
        
    }

    public function collect(Request $request, Response $response, Exception $exception = null)
    {

        $kernel = new \AppKernel('dev', true);
        $kernel->boot();
        $container = $kernel->getContainer();
        $router = $container->get('router');
        $classmapper = $container->get('classmapperservice');
        $data["header"] = "Url configuration";
        $data["uri"] = $request->getPathInfo();

        try {
            $route = $router->match($data["uri"]);

            $data["route"] = $route["_route"];
            $data["controller"] = $route["_controller"];
            if (array_key_exists("_locale", $route)) {
                $data["locale"] = $route["_locale"];

                $data["entityName"] = $route["entityName"];
                $data["entityClass"] = $classmapper->getEntityClass($route["entityName"], $data["locale"]);

                $configuratorService = $container->get('prototype.configurator.service');
                $namesOfServices = $configuratorService->getNamesOfServices();
                $service = $configuratorService->getService($data["route"], $data["entityClass"]);

                $data['config'] = $this->printServiceInfo('Config', $configuratorService);
                $data['twigs'] = $this->printTwigConfig($service->getConfig());

                $configuratorService = $container->get('prototype.gridconfig.configurator.service');
                $namesOfServices = $configuratorService->getNamesOfServices();
                $configuratorService->getService($data["route"], $data["entityClass"]);
                $data['gridconfig'] = $this->printServiceInfo('Grid', $configuratorService);

                $configuratorService = $container->get('prototype.formtype.configurator.service');
                $namesOfServices = $configuratorService->getNamesOfServices();
                $configuratorService->getService($data["route"], $data["entityClass"]);
                $data['formtype'] = $this->printServiceInfo('Form Type', $configuratorService);
            }
        } catch (Symfony\Component\Routing\Exception\ResourceNotFoundException $e) {
      
        }
        $data['twigChanges']=$this->twigChanges;
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

    protected function printTwigConfig($config)
    {

        $output = [];
        $output[] = "<br/><h2>Twigs</h2>";
        $output[] = "<table><thead><tr><th>Type</th><th>Twig</th></tr></thead><tbody>";
       
        foreach ($config as $name => $path) {

            if (strstr($path, 'CorePrototypeBundle:Default')) {
                $output[] = ("<tr><th>$name</th><td>$path</td></tr>");
            } else {
                $this->twigChanges++;
                $output[] = ("<tr><th>$name</th><td><span style='background-color: #aacd4e;border-radius: 6px; color: #fff; display: inline-block;margin-right: 2px;padding: 4px;'>$path</span></td></tr>");
            }
        }
        $output[] = "</tbody></table>";
        
        return $output;
    }

    protected function printServiceInfo($name, $configuratorService)
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
