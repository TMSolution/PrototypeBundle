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
class ChartGenerator  extends ServiceGenerator {

    
    protected function getDirectoryPath() {

        return "Resources" . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "services" . DIRECTORY_SEPARATOR . strtolower($this->getRootFolder()) . DIRECTORY_SEPARATOR;
    }
    
    

}
