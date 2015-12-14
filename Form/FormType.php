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

    public function getLabelPrefix($entityClass) {

        $entityClassArr = explode("\\", $entityClass);
        $namespaceArr = [];
        foreach ($entityClassArr as $element) {
            
            if($element=='Entity'){
               
                break;
            }
            $namespaceArr[] = strtolower($element);
        }
        $namespaceArr[count($namespaceArr)-1]=  str_replace('bundle','',$namespaceArr[count($namespaceArr)-1]);
        return implode(".",$namespaceArr);
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {


        //nazwa obiektu
        //caly array
        $test = $this->model->getFieldsInfo();
        $entityClass = $this->model->getEntityClass();
        $objectName = $this->getLabelPrefix($entityClass).'.'.$this->model->getEntityName();
        
        //get type, it works
        //dump(array_values($test)[0]["type"]);exit;
        //get length, it works
        //dump(array_values($test)[1]["length"]);exit;
        foreach ($this->model->getFieldsinfo() as $key => $object) {


      
            $label = $objectName . '.' . strtolower($key);
            if (!array_key_exists("association", $object) || $object["association"] != "OneToMany") {
                if ($object["type"] == "datetime") {
                    $builder->add($key, "datetime", [
                        'label' => $label,
                        'widget' => 'single_text',
                        'format' => 'dd.MM.yyyy',
                        'attr' => [
                            'placeholder' => '',
                            'class' => 'datepicker',
                            'data-date-format' => "dd.mm.yyyy",
                            'data-provide' => 'datepicker']]
                    );
                } else {

                    $builder->add($key, null, [
                        'label' => $label
                    ]);
                }
                //for block to display 'id' in form

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
