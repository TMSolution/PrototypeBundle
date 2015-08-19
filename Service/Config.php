<?php

namespace Core\PrototypeBundle\Service;

class Config {

    protected $config = [];

    public function __construct($defaultConfig, array $config=[]) {

        if (empty($config)) {
            $this->config = $defaultConfig;
        } else {
 
            $this->config = array_merge($defaultConfig, $config);
        }
    }

    public function merge(array $config) {

        $this->config = array_merge($config, $this->config);
    }

    public function get($property) {
        if (array_key_exists($property, $this->config)) {
            return $this->config[$property];
        } else {
            throw new \Exception(" $property doesn't exists in Config.");
        }
    }

}
