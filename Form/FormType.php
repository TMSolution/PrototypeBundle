<?php

namespace Core\PrototypeBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class FormType extends BaseFormType {

    private $class = null;
    protected $metadata = null;
    protected $model = null;

    /**
     * @param string $class The Group class name
     */
    public function __construct($class = null, $metadata = null, $model = null) {
        $this->class = $class;
        $this->metadata = $metadata;
        $this->model = $model;
    }

    public function setClass($class) {
        $this->class = $class;
    }

    public function setMetadata($metadata) {
        $this->metadata = $metadata;
    }

    public function setModel($model) {
        $this->model = $model;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {

        //$mergeFieldNames=  array_merge($this->metadata->getFieldNames(), $this->metadata->getAssociationNames());    
        //var_dump($this->metadata->getReflectionClass()->getProperties()); 
//        foreach ($this->metadata->getReflectionClass()->getProperties() as $key => $object) {
//
//            if ($this->checkProperty($object->name)) {
//                $object = $builder->add($object->name);
//            }
//        }
        //caly array
        $test = $this->model->getFieldsInfo();
        //dump($test);exit;
        //get type, it works
        //dump(array_values($test)[0]["type"]);exit;
        //get length, it works
        //dump(array_values($test)[1]["length"]);exit;
        foreach ($this->model->getFieldsinfo() as $key => $object) {
            $builder->add($key);
        }
//        for($i=0; $i++; $i<count($test)){
//            if(array_values($test)[$i]) == integer){
//                echo('dupa');
//            }
//        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => $this->metadata->name,
            'attr' => array("class" => $this->class),
        ));
//        dump($resolver);exit;
    }

    public function getName() {

        return strtolower(str_replace('\\', '', str_replace('Bundle\\Entity\\', '_', $this->metadata->name)));
    }

    private function camelize($string) {
        return preg_replace_callback('/(^|_|\.)+(.)/', function ($match) {
            return ('.' === $match[1] ? '_' : '') . strtoupper($match[2]);
        }, $string);
    }

    private function checkProperty($property) {
        $objectName = $this->metadata->name;
        $object = new $objectName();

        $camelProp = $this->camelize($property);
        $reflClass = new \ReflectionClass($object);
        $getter = 'get' . $camelProp;
        $setter = 'set' . $camelProp;
        $isser = 'is' . $camelProp;
        $hasser = 'has' . $camelProp;
        $classHasProperty = $reflClass->hasProperty($property);


        // var_dump($getter);

        if ($reflClass->hasMethod($getter) && $reflClass->getMethod($getter)->isPublic() && $reflClass->hasMethod($setter) && $reflClass->getMethod($setter)->isPublic()) {
            return true;
        } elseif ($reflClass->hasMethod($isser) && $reflClass->getMethod($isser)->isPublic()) {
            return true;
        } elseif ($reflClass->hasMethod($hasser) && $reflClass->getMethod($hasser)->isPublic()) {
            return true;
        } elseif ($reflClass->hasMethod('__get') && $reflClass->getMethod('__get')->isPublic()) {
            return true;
        } elseif ($classHasProperty && $reflClass->getProperty($property)->isPublic()) {
            return true;
        } elseif (!$classHasProperty && property_exists($object, $property)) {
            return true;
        } /*
         * magicCall to boolean wynikający niewiadomo z czego
         * elseif ($this->magicCall && $reflClass->hasMethod('__call') && $reflClass->getMethod('__call')->isPublic()) {
          return true;
          } */
        return false;
    }

    public function __toString() {
        return "";
    }

}
