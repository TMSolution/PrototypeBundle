<?php

namespace Core\PrototypeBundle\Service;

class Config {

    protected $config = [];
    protected $configComponents = [];
    protected $loaded = false;

    public function __construct(/* you can pass many Config objects and/or arrays */) {
        
        $this->configComponents = func_get_args();
       
    }

    protected function load() {
    
        if ($this->loaded == false) {
            foreach ($this->configComponents as $component) {
                if (is_array($component)) {
                    $this->config = array_merge($this->config, $component);
                } else if (is_object($component) && get_class($component) == "Core\PrototypeBundle\Service\Config") {
                    $this->config = array_merge($this->config, $component->getConfig());
                } else {
                    throw new \Exception('Bad type of component, you can pass only arrays or Core\PrototypeBundle\Service\Config objects to this class ');
                }
            }
        } else {
            $this->loaded=true;
            return $this->config;
        }
    }

    public function getConfig() {
        
        $this->load();
        return $this->config;
    }

    public function merge(array $config) {
        
        $this->load();
        $this->config = array_merge($this->config, $config);
    }

    public function get($property) {
        
        $this->load();
        if (array_key_exists($property, $this->config)) {
            return $this->config[$property];
        } else {
            throw new \Exception(" $property doesn't exists in Config.");
        }
    }

}
