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
abstract class YamlGenerator extends AbstractGenerator {

    protected $container;
    protected $shortEntityName;
    protected $entityName;
    protected $templatePath;
    protected $fileName;
    protected $rootFolder;
    protected $directory;
    protected $bundleName;
   

    public function __construct($container, $entityName, $rootFolder, $prefix = null, $subPrefix = null, $parentEntity = null) {

        $this->container = $container;
        $this->rootFolder = $rootFolder;
        $this->entityName = $entityName;
        $this->prefix= $prefix;
        $this->subPrefix= $subPrefix;
        $this->parentEntity=$parentEntity;
    }

    public function getBundleName() {
        $model = $this->getContainer()->get("model_factory")->getModel($this->getEntityName());
        $metadata = $model->getMetadata();
        return str_replace('\\', '', str_replace('\Entity', null, $metadata->namespace));
    }

    public function getEntityName() {
        return $this->entityName;
    }

    public function getRootFolder() {
        return $this->rootFolder;
    }

    protected function convertSeparatorsToDots() {
        return str_replace(DIRECTORY_SEPARATOR, '.', strtolower($this->getRootFolder()));
    }

    protected function getFileName() {


        return $this->getDirectory() . DIRECTORY_SEPARATOR . strtolower($this->getEntityShortName()) . ".yml";
    }

    protected function getDirectoryPath() {

        return "Resources" . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "services" . DIRECTORY_SEPARATOR . strtolower($this->getRootFolder()) . DIRECTORY_SEPARATOR . strtolower($this->getEntityShortName());
    }

    protected function getDirectory() {

        if (!$this->directory) {


            $entityReflection = new ReflectionClass($this->getEntityName());
            $entityNamespace = $entityReflection->getNamespaceName();

            $directory = str_replace("\\", DIRECTORY_SEPARATOR, ($this->getClassPath() . "\\" . $entityNamespace));
            $this->directory = $this->replaceLast("Entity", $this->getDirectoryPath(), $directory);

            if (is_dir($this->directory) == false && mkdir($this->directory, 0777, true) == false) {
                throw new UnexpectedValueException("Creating directory failed: " . $directory);
            }
        }
        return $this->directory;
    }

    protected function readYml() {

      
            if (file_exists($this->getFileName())) {
                $yaml = new Parser();
                $yamlArr = $yaml->parse(file_get_contents($this->getFileName()));
                if ($yamlArr === NULL) {
                    $yamlArr = ['parameters' => [], 'services' => []];
                }
            } else {
                $yamlArr = ['parameters' => [], 'services' => []];
            }

           
        
        return $this->repairApostrophes($yamlArr);
    }

    protected function writeYml($yml,$fileName) {
        $yaml = new Dumper();

        $this->repairApostrophes($yml);
        $yamlData = $yaml->dump($yml, 4, 0, false, true);
        file_put_contents($fileName, $yamlData);
    }

    protected function repairApostrophes(&$yamlArr) {

        foreach ($yamlArr as $key => $value) {
            if (is_array($value)) {

                $this->repairApostrophes($yamlArr[$key]);
            } else {
                
                
                if(!is_bool($value)){
                $yamlArr[$key] = "'" . $value . "'";
                }
            }
        }

        return $yamlArr;
    }


}
