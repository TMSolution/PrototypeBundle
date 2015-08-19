<?php

namespace Core\PrototypeBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class BlankType extends BaseFormType {

    private $externalFields = [];
    
    /**
     *
     * @var type 
     */
    protected $name = null;

    /**
     * @param string $class The Group class name
     */
    public function __construct() {
        
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
      
        foreach($this->externalFields as $field)
        {
            $builder->add($field["name"],$field["type"]/*, $field["options"]*/);
            
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }
    
    public function add($name,$type,$options) {
        
        $this->externalFields[]=array("name"=>$name,"type"=>$type,"options"=>$options);
    }

}
