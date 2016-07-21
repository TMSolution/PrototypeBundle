<?php

/**
 * Copyright (c) 2014, TMSolution
 * All rights reserved.
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace Core\PrototypeBundle\Generator\ServiceStrategy;


class ConfigStrategy extends DefaultStrategy {


    function getClassNamespace() {
        return 'Core\\PrototypeBundle\\Service\\Config';
    }

    function getArguments() {
        return ["%prototype_config_params%", "%".$this->getConfigParameterName()."%"];
    }
    
    protected function getConfigParameterName() {

        return sprintf("%s.%s", $this->getServiceName(), "parameters");
    }
}
