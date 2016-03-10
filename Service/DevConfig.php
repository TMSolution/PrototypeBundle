<?php

namespace Core\PrototypeBundle\Service;

class DevConfig {

    protected $overriden = false;
    protected $cofnig= null;
    
    public function __construct($config)
    {
        $this->config=$config;
        $this->configComponents=$config->getConfigComponents();
        
    }

    protected function findDifferneces($previousConfig, $config) {

        $baseConfig = $this->configComponents[0];

        if (is_object($baseConfig)) {

            $baseConfig = $baseConfig->getConfig();
        }

        foreach ($config as $key => $value) {

            if (isset($baseConfig[$key])) {
                if ($baseConfig[$key] !== $config[$key]) {
                    $config[$key] = $this->markChanged($value);
                }
            } else {
                $config[$key] = $this->markChanged($value);
            }
        }
        return $config;
    }

    public function getConfigWithDifferences() {

        $config = [];

        foreach ($this->configComponents as $configComponent) {

            if (is_object($configComponent)) {
                $configComponent = $configComponent->getConfig();
            }

            $previousConfig = $config;
            $config = array_replace_recursive($config, $configComponent);
            $config = $this->arrayRecursiveDiff($previousConfig, $config);
        }

        return $config;
    }

    function arrayRecursiveDiff($firstArray, $secondArray) {


        $resultArray = [];

        foreach ($secondArray as $key => $value) {
            if (array_key_exists($key, $firstArray)) {

                if (is_array($value)) {
                    $resultArray[$key] = $this->arrayRecursiveDiff($firstArray[$key], $secondArray[$key]);
                } else if ($firstArray[$key] == (string) $value) {
                    $resultArray[$key] = $value;
                } else {

                    $this->overriden = true;
                    $resultArray[$key] = new ValueObject($value);
                }
            } else {
                if (!is_array($value)) {

                    $this->overriden = true;
                    $resultArray[$key] = new ValueObject($value);
                } else {
                    $resultArray[$key] = $value;
                }
            }
        }
        return $resultArray;
    }

    function isOverridden() {

        return $this->overriden;
    }

}
