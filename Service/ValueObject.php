<?php

namespace Core\PrototypeBundle\Service;



namespace Core\PrototypeBundle\Service;


class ValueObject
{
    protected $value;
    
    public function __construct($value)
    {
        $this->value=$value;
    }
    
    public function __toString()
    {
        return (string)$this->value;
    }
    
    public function changed()
    {
        return true;
    }
    
}
