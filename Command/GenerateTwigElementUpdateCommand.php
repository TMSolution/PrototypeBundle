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
use Symfony\Component\Console\Input\InputOption;
use Doctrine\Bundle\DoctrineBundle\Mapping\DisconnectedMetadataFactory;
use ReflectionClass;
use LogicException;
use UnexpectedValueException;

/**
 * GridConfigCommand generates widget class and his template.
 * @author Mariusz Piela <mariuszpiela@gmail.com>
 */
class GenerateTwigElementUpdateCommand extends AbstractGenerateTwigCommand
{

    protected function configure()
    {
        /*
         * type "OPTIONAL" for the parameter "rootFolder" was chosen because of compatibility with the global command prototype:generate:files. 
         * Please do not change!
         */
        $this->setName('prototype:generate:twig:element:update')
                ->setDescription('Generate twig element update template.')
                ->addArgument('configBundle', InputArgument::REQUIRED, 'Insert config bundle name or entity path')
                ->addArgument('entity', InputArgument::OPTIONAL, 'Insert entity class name')
                ->addArgument('rootFolder', InputArgument::OPTIONAL, 'Insert rootFolder')
                ->addOption('withAssociated', null, InputOption::VALUE_NONE, 'Insert associated param');
        
        $this->viewType=self::Element;
        $this->templatePath="CorePrototypeBundle:Command:element.update.template.twig";
        $this->fileName="update.html.twig";
        
    }


}
