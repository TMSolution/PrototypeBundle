<?php

namespace Core\PrototypeBundle\Profiler;

use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

use Exception;

/**
 * @author Krzysiek Piasecki <krzysiekpiasecki@gmail.com>
 */
class PrototypeCollector implements DataCollectorInterface
{

    private $router = null;
    
    public function __construct()
    {
        //$this->router = $router;
    }
    
    public function collect(Request $request, Response $response, Exception $exception = null)
    {
        $this->data = array(
            'locale' => $request->getLocale(),
            'memory' => memory_get_peak_usage(true),
            'uri' => $request->getRequestUri(),
            'basePath' => $request->getBasePath(),                  
        );
    }
    
    public function getData()
    {
        return $this->data;
    }

    public function getUri()
    {
        return $this->data['uri'];
    }
    
    public function getMemory()
    {
        return $this->data['memory'];
    }
            
    public function getName()
    {
        return "prototype";
    }

}
