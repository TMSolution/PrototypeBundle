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

    public function getGridConfig($entityName) {

        if (isset($this->gridConfigLists[$entityName])) {
            return $this->gridConfigLists[$entityName];
        }

        $gridConfig = $this->createGridConfig($entityName);

        $this->gridConfigLists[$entityName] = $gridConfig;

        return $gridConfig;
    }

    protected function createGridConfig($entityName) {
        $metadata = $this->manager->getClassMetadata($entityName);
        $gridConfigName = str_replace('\\Entity\\', '\\GridConfig\\', $metadata->name);
        return new $gridConfigName($this->container);
    }

}
