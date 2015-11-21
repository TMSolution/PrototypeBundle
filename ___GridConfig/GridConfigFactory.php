<?php

namespace Core\PrototypeBundle\GridConfig;

class GridConfigFactory {

    protected $container = null;
    protected $manager = null;
    protected $gridConfigLists = array();

    public function __construct($container) {

        $this->container = $container;
        $this->manager = $this->container->get('doctrine')->getManager();
    }

    public function getGridConfig($entityClass) {

        if (isset($this->gridConfigLists[$entityClass])) {
            return $this->gridConfigLists[$entityClass];
        }

        $gridConfig = $this->createGridConfig($entityClass);

        $this->gridConfigLists[$entityClass] = $gridConfig;

        return $gridConfig;
    }

    protected function createGridConfig($entityClass) {
        $metadata = $this->manager->getClassMetadata($entityClass);
        $gridConfigName = str_replace('\\Entity\\', '\\GridConfig\\', $metadata->name);
         if (true === class_exists($gridConfigName) ) {
         return new $gridConfigName($this->container);
         
         }
    }

}
