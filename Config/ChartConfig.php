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
    protected $colors = ['#F44336', '#E91E63', '#9C27B0', '#673AB7', '#3F51B5', '#2196F3', '#03A9F4', '#00BCD4', '#009688', '#4CAF50', '#8BC34A', '#CDDC39', '#FFEB3B', '#FFC107', '#FF9800', '#FF5722', '#795548', '#795548', '#607D8B'];
   

    public function __construct($container)
    {
        $this->container = $container;
    }

    protected function getContainer()
    {
        return $this->container;
    }

   

}
