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

/**
 * GridConfigCommand generates widget class and his template.
 * @author Mariusz Piela <mariuszpiela@gmail.com>
 */
class ParametersGenerator extends YamlGenerator {

    protected $value;
    protected $viewType;

    public function __construct($container, $entityName, $rootFolder, $actionName, $viewType, $value) {

        parent::__construct($container, $entityName, $rootFolder);
        $this->actionName = $actionName;
        $this->viewType = $viewType;
        $this->value = $value;
    }

    public function getActionName() {
        return $this->actionName;
    }

    public function getViewType() {
        return strtolower($this->viewType);
    }

    public function getValue() {
        return $this->value;
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

                throw new \Exception("Internal attribute in chain must be an array");
            }
        } else {

            $array[$parameter] = [];
        }

        $parameterArr = &$array[$parameter];
        $this->createParam($parameterArr, $parametersArr, $value);
    }

    protected function divideParametersChain($parametersChain) {
        return explode(".", $parametersChain);
    }

    protected function getChain($parameters) {
        return array_merge($this->divideParametersChain($this->getActionName() . '.' . $parameters));
    }

    protected function createParametersBlock(&$yml) {

        
        
        if (!array_key_exists($this->getParameterName(), $yml['parameters'])) {
            $yml['parameters'][$this->getParameterName()] = [];
        }
        
        $parameter = &$yml['parameters'][$this->getParameterName()];
        
        $this->createParam($parameter, $this->getChain("templates." . $this->getViewType()), $this->getValue());
    
        //additinal strategies @to do 
        $actionName = $this->getActionName();

        switch ($actionName) {
            case "create":
                $this->createParam($parameter, $this->getChain("redirect"), true);
                break;
            case "update":
                $this->createParam($parameter, $this->getChain("redirect"), false);
                break;
            case "list":
                $this->createParam($parameter, $this->getChain("limit"), 10);
                $this->createParam($parameter, $this->getChain("hydrateMode"), 1);
                break;
        }
        
        
        $parameterObj = new \stdClass();
        $parameterObj->name = $this->getParameterName();
        $parameterObj->body = $yml['parameters'][$this->getParameterName()];
        return $parameterObj;
        
    }

    public function getParameterName() {

        return sprintf("%s.%s.config.parameters", $this->convertSeparatorsToDots(), strtolower($this->getEntityShortName()));
    }

    public function generate() {
        
        $yml = $this->readYml();
        $parameter=$this->createParametersBlock($yml);
        $this->writeYml($yml, $this->getFileName());
        return $parameter;
    }

    public function getInstance($entityName, $directory) {


        return new static($this->getContainer(), $entityName, $directory, FALSE, $this->getActionName(), $this->getViewType(), $this->getValue());
    }

}
