<?php

namespace Core\PrototypeBundle\Service;

class ControllerParams implements \ArrayAccess, \Countable
{

    protected $_values = array();
    
    public function setArray(array $_values)
    {
        $this->_values = $_values;
        
    }
    
    public function getArray()
    {
        return $this->_values;
    }
    
    public function count($mode = 'COUNT_NORMAL')
    {
        return count($this->_values);
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->_values);
    }

    public function offsetGet($offset)
    {

        return $this->offsetExists($offset) ? $this->_values[$offset] : NULL;
    }

    public function offsetSet($offset, $value)
    {

        $this->_values[$offset] = $value;
    }

    public function offsetUnset($offset)
    {

        if ($this->offsetExists($offset)) {

            unset($this->_values[$offset]);
        }
    }

}
