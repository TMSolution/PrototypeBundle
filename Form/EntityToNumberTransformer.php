<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of EnittyToNumberTransformer
 *
 * @author Jacek Łoziński <jacek.lozinski@tmsolution.pl> - jasne
 */

namespace Core\PrototypeBundle\Form;

use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class EntityToNumberTransformer implements DataTransformerInterface
{
    protected $model;

    public function __construct($model)
    {
        $this->model = $model;
    }

    /**
     * Transforms an object (issue) to a string (number).
     *
     * @param  Issue|null $entity
     * @return string
     */
    public function transform($entity)
    {
        
        if (null === $entity) {
            return '';
        }

        return $entity->getId();
    }

    /**
     * Transforms a string (number) to an object (issue).
     *
     * @param  string $entityNumber
     * @return Issue|null
     * @throws TransformationFailedException if object (issue) is not found.
     */
    public function reverseTransform($entityNumber)
    {
        // no issue number? It's optional, so that's ok
        if (!$entityNumber) {
            return;
        }

        $entity = $this->model
            ->findOneById($entityNumber)
        ;

        if (null === $entity) {
            // causes a validation error
            // this message is not shown to the user
            // see the invalid_message option
            throw new TransformationFailedException(sprintf(
                'An issue with number "%s" does not exist!',
                $entityNumber
            ));
        }

        return $entity;
    }
}