<?php

namespace Core\PrototypeBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class FormType extends BaseFormType {


    protected $model = null;

    /**
     * @param string $class The Group class name
     */
    public function __construct(/* $class = null, $metadata = null, */$model = null) {
        
        $this->model = $model;
    }

    public function setModel($model) {
        $this->model = $model;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {


        //caly array
        $test = $this->model->getFieldsInfo();
        //dump($test);exit;
        //get type, it works
        //dump(array_values($test)[0]["type"]);exit;
        //get length, it works
        //dump(array_values($test)[1]["length"]);exit;
        foreach ($this->model->getFieldsinfo() as $key => $object) {

           if(!array_key_exists("association", $object) ||  $object["association"]!="OneToMany")
            {
            //for block to display 'id' in form
            $builder->add($key);
            if ($key == "id") {
                $builder->add('id', 'hidden', [
                    'mapped' => false,                   
                ]);
            }
            }
        }
        
       
    }

    public function getName() {

        return strtolower(str_replace('\\', '', str_replace('Bundle\\Entity\\', '_', $this->model->getEntityName())));
    }

    private function camelize($string) {
        return preg_replace_callback('/(^|_|\.)+(.)/', function ($match) {
            return ('.' === $match[1] ? '_' : '') . strtoupper($match[2]);
        }, $string);
    }

    public function __toString() {
        return "";
    }

}
