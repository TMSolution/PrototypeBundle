<?php

namespace Core\PrototypeBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * 
 */
class KnpPaginatorListener
{

    /**
     * @var ContainerInterface 
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST == $event->getRequestType()) {

            $request = $event->getRequest();

            $this->route = $request->attributes->get('_route');
            $this->params = array_merge($request->query->all(), $request->attributes->get('_route_params', array()));
        }
    }

}
