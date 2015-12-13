<?php

namespace Core\PrototypeBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;


class ContainerAwareType extends AbstractType implements ContainerAwareInterface {

    protected $container;

    public function setContainer(ContainerInterface $container = null) {
        $this->container = $container;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        /** @var OptionResolver $resolver */
        $this->configureOptions($resolver);
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'container' => $this->container
        ));
    }

    

    public function getName() {
        return 'container_aware';
    }

    public function getParent() {
        return 'form';
    }

}
