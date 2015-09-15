<?php

namespace Core\PrototypeBundle\Form;

class FormTypeFactory {

    protected $container = null;
    protected $manager = null;
    protected $formTypeList = array();

    public function __construct($container) {
        
       

        $this->container = $container;
        $this->manager = $this->container->get('doctrine')->getManager();
    }
    
    

    public function getFormType($entityName, $class,$model) {

        //var_dump($entityName);

        if (isset($this->formTypeList[$entityName])) {
            return $this->formTypeList[$entityName];
        }

        $formType = $this->createFormType($entityName, $class,$model);

        $this->formTypeList[$entityName] = $formType;

        return $formType;
    }

    protected function createFormType($entityName, $class,$model) {
        $metadata = $this->manager->getClassMetadata($entityName);
        $formTypeName = str_replace('\\Entity\\', '\\Form\\', $metadata->name . 'Type');
        if (false === class_exists($formTypeName) ) {
            $formType = new FormType($class, $metadata,$model);
            return $formType;
        }
        
        $formType = new $formTypeName($class, $params = array());
        return $formType;
    }

}
