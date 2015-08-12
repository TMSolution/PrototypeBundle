<?php

namespace Core\PrototypeBundle\Form\Type;

class FormTypeFactory {

    protected $container = null;
    protected $manager = null;
    protected $formTypeList = array();

    public function __construct($container) {
        
       

        $this->container = $container;
        $this->manager = $this->container->get('doctrine')->getManager();
    }
    
    

    public function getFormType($entityName, $class) {

        //var_dump($entityName);

        if (isset($this->formTypeList[$entityName])) {
            return $this->formTypeList[$entityName];
        }

        $formType = $this->createFormType($entityName, $class);

        $this->formTypeList[$entityName] = $formType;

        return $formType;
    }

    protected function createFormType($entityName, $class) {
        $metadata = $this->manager->getClassMetadata($entityName);


        $formTypeName = str_replace('\\Entity\\', '\\Form\\Type\\', $metadata->name . 'Type');
        if (class_exists($formTypeName) === false) {
            $formType = new FormType($class, $metadata);
           $formType->setContainer($this->container);
            $formType->setTranslator($this->container->get('translator'));
            return $formType;
        }


        $formType = new $formTypeName($class, $params = array());
        $formType->setContainer($this->container);
        $formType->setTranslator($this->container->get('translator'));
        return $formType;
    }

}
