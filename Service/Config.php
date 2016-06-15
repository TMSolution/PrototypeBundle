<?php

namespace Core\PrototypeBundle\Service;


class Config {

    protected $overrided=false;
    protected $config = [];
    protected $configComponents = [];
    protected $loaded = false;

    public function __construct(/* you can pass many Config objects and/or arrays */) {

        $this->configComponents = func_get_args();
    }

    public function getConfigComponents()
    {
        return $this->configComponents;
    }

    protected function load() {

        if ($this->loaded == false) {

          
            foreach ($this->configComponents as $component) {
                if (is_array($component)) {
                    $this->mergeComponents($component);
                } else if (is_object($component) && get_class($component) == "Core\PrototypeBundle\Service\Config") {
                    $this->mergeComponents($component->getConfig());
                } else {
                    throw new \Exception('Bad type of component, you can pass only arrays or Core\PrototypeBundle\Service\Config objects to this class ');
                }
            }
        } else {
            $this->loaded = true;
            return $this->config;
        }
    }

    protected function mergeComponents($array) {

        if (is_array($array)) {
            if (empty($this->config)) {
                $this->config = $array;
            } else {

                $this->config = array_replace_recursive($this->config, $array);
            }
        }
    }

    public function getConfig() {

        $this->load();
        return $this->config;
    }


    public function merge(array $config) {

        $this->load();
        $this->config = $this->mergeComponents($config);
    }

    public function get($property) {

        $this->load();
        $propertyArr = explode('.', $property);


        $result = null;
        foreach ($propertyArr as $value) {
            if (!$result) {
                $result = $this->config[$value];
            } else {
                $result = $result[$value];
            }
        }
        return $result;
    }

    public function has($property) {

        $this->load();
        $propertyArr = explode('.', $property);

        $result = null;
        foreach ($propertyArr as $value) {
            if (!$result) {
                if (isset($this->config[$value])) {
                    $result = $this->config[$value];
                }
            } else {

                if (isset($result[$value])) {
                    $result = $result[$value];
                }
            }
        }

        if ($result != null) {
            return true;
        }
    }


    
   

}
