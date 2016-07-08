<?php

/**
 * Copyright (c) 2014, TMSolution
 * All rights reserved.
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace Core\PrototypeBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\Bundle\DoctrineBundle\Mapping\DisconnectedMetadataFactory;
use ReflectionClass;
use LogicException;
use UnexpectedValueException;

/**
 * GridConfigCommand generates widget class and his template.
 * @author Mariusz Piela <mariuszpiela@gmail.com>
 */
class GenerateTwigContainerViewCommand extends AbstractGenerateTwigCommand 
{

    
    protected function configure()
    {
        $this->setName('prototype:generate:twig:container:view')
                ->setDescription('Generate container twig for view action.')
                ->addArgument('configBundle', InputArgument::REQUIRED, 'Insert config bundle name or entity path')
                ->addArgument('entity', InputArgument::REQUIRED, 'Insert entity class name')
                ->addArgument('rootFolder', InputArgument::OPTIONAL, 'Insert rootFolder');
        
        $this->viewType=self::Container;
        $this->templatePath="CorePrototypeBundle:Command:container.view.template.twig";
        $this->fileName="view.html.twig";        
        
        
    }

}
