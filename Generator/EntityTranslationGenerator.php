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
class EntityTranslationGenerator extends TranslationGenerator {

    public function __construct($container, $entityName, $language) {
        $this->container = $container;
        $this->entityName = $entityName;
        $this->language = $language;
    }

    public function generate() {

        $arr = explode("\\Entity", $this->entityName);
        $bundle = $arr[0];
        $prefix = strtolower(str_replace("Bundle", "", str_replace("\\", ".", $bundle))).".". strtolower(str_replace("\\","",$arr[1]));
        $phrases = [];



        foreach ($this->getExtendedFieldsInfo() as $field => $parameters) {

            $label = strtolower(sprintf("%s.%s", $prefix, $field));
            if (in_array($parameters['type'], ["date", "datetime"])) {
                $phrases[$label . "_date_from"] = "Date from";
                $phrases[$label . "_date_to"] = "Date to ";
            } else

            if ($parameters['is_object']) {

                if ($parameters["default_field"] == "name") {
                    
                    if(substr($field,-1)=="s")
                    {
                        $value=sprintf("All %ses",substr($field,0,-1));
                    }
                    else
                    { 
                        $value= sprintf("All %ss", strtolower($field));
                    }
                    $phrases[sprintf("%s.%s_all", $label, strtolower($parameters["default_field"]))] = $value ;
                   // dump(sprintf("%s.%s_all", $label, $parameters["default_field"]));
                }
            } else {
                    $phrases[strtolower($label)] = $field;
            }
        }


        $translationGenerator = new TranslationGenerator($this->getContainer(), $bundle, $this->language, $phrases);
        $translationGenerator->generate();
    }

}
