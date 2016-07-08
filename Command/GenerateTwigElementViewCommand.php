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
class GenerateTwigElementViewCommand extends ContainerAwareCommand {

    protected function configure() {
        /*
         * type "OPTIONAL" for the parameter "rootFolder" was chosen because of compatibility with the global command prototype:generate:files. 
         * Please do not change!
         */
        $this->setName('prototype:generate:twig:element:view')
                ->setDescription('Generate twig element view template.')
                ->addArgument('configBundle', InputArgument::REQUIRED, 'Insert config bundle name or entity path')
                ->addArgument('entity', InputArgument::OPTIONAL, 'Insert entity class name')
                ->addArgument('rootFolder', InputArgument::OPTIONAL, 'Insert rootFolder')
                ->addArgument('viewType', InputArgument::REQUIRED, 'Insert view Type')
                ->addArgument('templatePath', InputArgument::REQUIRED, 'Insert template Path')
                ->addArgument('fileName', InputArgument::REQUIRED, 'Insert file name for new file')
                ->addOption('withAssociated', null, InputOption::VALUE_NONE, 'Insert associated param');
        
        $this->viewType=self::Element;
        $this->templatePath="CorePrototypeBundle:Command:element.view.template.twig";
        $this->fileName="view.html.twig";
    }

}
