<?php

namespace Core\PrototypeBundle\Service;

use Symfony\Component\DependencyInjection\Container;
use Vich\UploaderBundle\Naming\DirectoryNamerInterface;
use Vich\UploaderBundle\Mapping\PropertyMapping;

class FormService {

    protected $container;
    protected $formFactory;

    public function __construct(Container $container) {
        $this->container = $container;
        $this->formFactory = $this->container->get('form.factory');
    }

    public function getFormView($formTypeClass, $entity, $formParams) 
    {      
        if(is_string($entity))
        {
           $entity=$this->container->get("model_factory")->getModel($entity)->getEntity();
        }
        
        $formType = new $formTypeClass();
        return $this->formFactory->createBuilder($formType, $entity, $formParams)->getForm()->createView();
    }
}