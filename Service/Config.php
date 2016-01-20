<?php

namespace Core\PrototypeBundle\Service;

class Config {

    protected $config = [];
    protected $configComponents = [];
    protected $loaded = false;

    public function __construct(/* you can pass many Config objects and/or arrays */) {

        $this->configComponents = func_get_args();
    }

//    protected function load() {
//    
//        if ($this->loaded == false) {
//            foreach ($this->configComponents as $component) {
//                if (is_array($component)) {
//                    $this->config = array_merge($this->config, $component);
//                } else if (is_object($component) && get_class($component) == "Core\PrototypeBundle\Service\Config") {
//                    $this->config = array_merge($this->config, $component->getConfig());
//                } else {
//                    throw new \Exception('Bad type of component, you can pass only arrays or Core\PrototypeBundle\Service\Config objects to this class ');
//                }
//            }
//        } else {
//            $this->loaded=true;
//            return $this->config;
//        }
//    }


    protected function load() {

        if ($this->loaded == false) {


            foreach ($this->configComponents as $component) {
                if (is_array($component)) {
                    $this->mergeSubArray($component);
                } else if (is_object($component) && get_class($component) == "Core\PrototypeBundle\Service\Config") {
                    $this->mergeSubArray($component->getConfig());
                } else {
                    throw new \Exception('Bad type of component, you can pass only arrays or Core\PrototypeBundle\Service\Config objects to this class ');
                }
            }
        } else {
            $this->loaded = true;
            return $this->config;
        }
    }

    public function mergeSubArray($array) {

        if (is_array($array)) {


            if (empty($this->config)) {

                $this->config = $array;
            } else {

                if (array_key_exists('base', $array) && is_array($array['base'])) {
                    $array['base']['templates'] = array_merge($this->config['base']['templates'], $array['base']['templates']);
                    $this->config['base'] = array_merge($this->config['base'], $array['base']);
                }
                if (array_key_exists('actions', $array) && is_array($array['actions']) ) {
                    foreach ($array['actions'] as $action => $value) {

                        $array['actions'][$action]['templates'] = array_merge($this->config['actions'][$action]['templates'], $array['actions'][$action]['templates']);
                        $this->config['actions'][$action] = array_merge($this->config['actions'][$action], $array['actions'][$action]);
                    }
                }

                if (array_key_exists('routings', $array) && is_array($array['routings'])) {
                    $this->config['routings'] = array_merge($this->config['routings'], $array['routings']);
                }
            }
        }
    }

    public function getConfig() {

        $this->load();
        return $this->config;
    }

    public function merge(array $config) {

        $this->load();
        $this->config = $this->mergeSubArray($config);
    }

    public function get($property) {

        $this->load();
        if (array_key_exists($property, $this->config)) {
            return $this->config[$property];
        } else {
            throw new \Exception(" $property doesn't exist in Config.");
        }
    }

    public function has($property) {

        $this->load();
        if (array_key_exists($property, $this->config)) {
            return true;
        }
    }

}
