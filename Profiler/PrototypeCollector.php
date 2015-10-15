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
        $data["uri"] = $request->getRequestUri();
        $route = $router->match($data["uri"]);           
        $data["route"] = $route["_route"];
        $data["controller"] = $route["_controller"];
        $data["locale"] = $route["_locale"];
        $data["entityName"] = $route["entityName"];
        $data["entityClass"] = $classmapper->getEntityClass($route["entityName"], $data["locale"]);        
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

}
