<?php

namespace Core\PrototypeBundle\Event;

use Symfony\Component\EventDispatcher\Event as DispatcherEvent;

class Event extends DispatcherEvent
{

    protected $params;
    protected $model;
    protected $form;
    protected $list;
    protected $grid;
    

    public function getParams()
    {
        if ($this->params) {
            return $this->params;
        } else {
            throw new \Exception('Parameter "params" has not been set.');
        }
    }

    public function setParams($params)
    {
        $this->params = $params;
               
    }

    public function getModel()
    {
        if ($this->model) {
            return $this->model;
        } else {
            throw new \Exception('Parameter "model" has not been set.');
        }
    }

    public function setModel($model)
    {
        $this->model = $model;
    }

    public function getForm()
    {
        if ($this->form) {
            return $this->form;
        } else {
            throw new \Exception('Parameter "form" has not been set.');
        }
    }

    public function setForm($form)
    {
        $this->form = $form;
    }

    public function getList()
    {
        if ($this->list) {
            return $this->list;
        } else {
            throw new \Exception('Parameter "list" has not been set.');
        }
    }

    public function setList($list)
    {
        $this->list = $list;
    }

    public function getGrid()
    {
        if ($this->grid) {
            return $this->grid;
        } else {
            throw new \Exception('Parameter "grid" has not been set.');
        }
    }

    public function setGrid($grid)
    {
        $this->grid = $grid;
    }

}
