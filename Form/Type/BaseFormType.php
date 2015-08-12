<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Core\PrototypeBundle\Form\Type;

use Symfony\Component\Form\AbstractType;


/**
 * Description of BaseFormType
 * 
 * $formtype = "form-horizontal", "form-inline",
 *
 * @author Łukasz
 */
abstract class BaseFormType extends AbstractType
{
    protected $container;
    protected $translator;
    
    public function __construct(){}
    
    public function setContainer($container)
    {
        $this->container = $container;
    }
    
    public function setTranslator($translator){
        $this->translator =  $translator;
    }
    
    
    
    protected function setFormType($formtype = null)
    {
        if(isset($formtype))
        {
            return  $formtype;
        }
        else
        {
            return ;
        }
        
    }
    
    
}
