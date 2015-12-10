<?php

namespace Core\PrototypeBundle\Twig;

class UcWordsExtension extends \Twig_Extension
{
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('ucwords', 'ucwords')
        ];
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('ucwords', 'ucwords')
        ];
    }

    public function ucwords($text)
    {
        return ucfirst($text);
        
    }
    
    
    public function getName()
    {
        return 'ext.ucwords';
    }

}