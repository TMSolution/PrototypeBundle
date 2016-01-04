<?php

namespace Core\PrototypeBundle\Config;

/**
 * Description of ListConfig
 *
 * @author Mariusz
 */
abstract class IdFinder {

    protected $container;
    
    public function __construct($container) {
        $this->container = $container;
    }

    abstract public function getId();
}
