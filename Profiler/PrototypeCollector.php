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
class PrototypeCollector implements DataCollectorInterface {

    private $data = [];

    public function __construct() {
        
    }

    public function collect(Request $request, Response $response, Exception $exception = null) {
        $kernel = new \AppKernel('dev', true);
        $kernel->boot();
        $container = $kernel->getContainer();
        $router = $container->get('router');
        $classmapper = $container->get('classmapperservice');
        $data["header"] = "Url configuration";
        $data["uri"] = $request->getPathInfo();
        $route = $router->match($data["uri"]);
        $data["route"] = $route["_route"];
        $data["controller"] = $route["_controller"];
        $data["locale"] = $route["_locale"];
        $data["entityName"] = $route["entityName"];
        $data["entityClass"] = $classmapper->getEntityClass($route["entityName"], $data["locale"]);

        $configuratorService = $container->get('prototype.configurator.service');
        $service = $configuratorService->getService($data["route"], $data["entityName"]);
        $data['config'] = $this->printServiceInfo('Config', $configuratorService);
        $data['twigs'] = $this->printTwigConfig($service->getConfig());

        $configuratorService = $container->get('prototype.gridconfig.configurator.service');
        $configuratorService->getService($data["route"], $data["entityName"]);
        $data['gridconfig'] = $this->printServiceInfo('Grid', $configuratorService);

        $configuratorService = $container->get('prototype.formtype.configurator.service');
        $configuratorService->getService($data["route"], $data["entityName"]);
        $data['formtype'] = $this->printServiceInfo('Form Type', $configuratorService);

        $this->data = $data;
    }

    public function getData() {
        return $this->data;
    }

    public function getName() {
        return "prototype";
    }

    protected function printTwigConfig($config) {
        $output = [];
        $output[] = ("Twigs: ");
        foreach ($config as $name => $path) {
            $output[] = ("<strong>$name</strong>: $path");
        }
        return $output;
    }

    protected function printServiceInfo($name, $service) {
        $output = [];

        $serviceArray = $service->getChosen();
        if ($name) {
            $output[] = "<br/><h2>" . $name . "</h2>";
        }

        if ($serviceArray['phrase']) {
            $output[] = ("<strong>phrase</strong>: " . $serviceArray['phrase']);
        }

        if ($serviceArray['serviceid']) {
            $output[] = ("<strong>servicename:</strong>" . $serviceArray['serviceid']);
        } else {
            $output[] = ("<strong>servicename:</strong> none");
        }

        if ($serviceArray['service']) {
            $output[] = ("<strong>class</strong>:" . get_class($serviceArray['service']));
        } else {
            $output[] = ("<strong>class</strong>: none");
        }

        return $output;
    }

}
