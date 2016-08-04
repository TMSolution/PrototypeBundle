<?php

/**
 * Copyright (c) 2014, TMSolution
 * All rights reserved.
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace Core\PrototypeBundle\Generator;

use Doctrine\Bundle\DoctrineBundle\Mapping\DisconnectedMetadataFactory;
use ReflectionClass;
use LogicException;
use UnexpectedValueException;
use Core\PrototypeBundle\Component\Yaml\Parser;
use Core\PrototypeBundle\Component\Yaml\Dumper;

/**
 * GridConfigCommand generates widget class and his template.
 * @author Mariusz Piela <mariuszpiela@gmail.com>
 */

class TranslationGenerator extends YamlGenerator {

    protected $language;
    protected $translateArr;
    protected $bundle;
    protected $value;

    public function __construct($container, $bundle, $language, $translateArr) {
        $this->container = $container;
        $this->bundle = $bundle;
        $this->language = $language;
        $this->translateArr = $translateArr;
    }

    protected function getFileName() {

        $fileName=$this->getContainer()->get('kernel')->getRootDir().DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.$this->bundle. DIRECTORY_SEPARATOR . "Resources" . DIRECTORY_SEPARATOR . "translations" . DIRECTORY_SEPARATOR . sprintf("messages.%s.yml",$this->language);
     
        return $fileName; 
   }


    protected function readYml() {

        if (file_exists($this->getFileName())) {
            $yaml = new Parser();
            $yamlArr = $yaml->parse(file_get_contents($this->getFileName()));
            if ($yamlArr === NULL) {
                $yamlArr = [];
            }
        } else {
            throw new \Exception(sprintf("File %s not exists!",$this->getFileName()));
        }

        return $this->repairApostrophes($yamlArr);
    }

    public function createParam(&$array, $parametersArr, $value) {

        $parameter = array_shift($parametersArr);

        if (count($parametersArr) == 0) {

            if (!array_key_exists($parameter, $array) || $array[$parameter] == null) {
                $array[$parameter] = $value;
            }
            return;
        }

        if (array_key_exists($parameter, $array)) {
            if (!is_array($array[$parameter])) {

                throw new \Exception("Internal attribute in chain must be an array for chain $parameter");
            }
        } else {

            $array[$parameter] = [];
        }

        $parameterArr = &$array[$parameter];
        $this->createParam($parameterArr, $parametersArr, $value);
    }

    public function generate() {

        $yml = $this->readYml();
        
        foreach($this->translateArr as $key=>$value){
            $service = $this->createParam($yml, explode(".", $key),$value);
        }
        $this->writeYml($yml, $this->getFileName());
        return $service;
    }

}
