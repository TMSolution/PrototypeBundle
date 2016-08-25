<?php

namespace Core\PrototypeBundle\Config;

/**
 * Description of ListConfig
 *
 * @author Mariusz
 */
class ChartConfig
{

    protected $model;
    protected $container;
    protected $backgroundColorClass = ['bgm-red', 'bgm-pink', 'bgm-purple', 'bgm-deeppurple', 'bgm-indigo', 'bgm-blue ', 'bgm-lightblue', 'bgm-cyan', 'bgm-teal', 'bgm-green', 'bgm-lightgreen', 'bgm-lime', 'bgm-yellow', 'bgm-amber', 'bgm-orange', 'bgm-deeporange', 'bgm-brown', 'bgm-gray', 'bgm-bluegray'];
   

    public function __construct($container)
    {
        $this->container = $container;
    }

    protected function getContainer()
    {
        return $this->container;
    }

   

}
