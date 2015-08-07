<?php

namespace Core\PrototypeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('CorePrototypeBundle:Default:index.html.twig', array('name' => $name));
    }
}
