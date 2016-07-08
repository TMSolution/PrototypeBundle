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


use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Application;




/**
 * GridConfigCommand generates widget class and his template.
 * @author Mariusz Piela <mariuszpiela@gmail.com>
 */
class GenerateTwigElementReadCommand extends ContainerAwareCommand 
{

    protected function configure()
    {
        /*
         * type "OPTIONAL" for the parameter "rootFolder" was chosen because of compatibility with the global command prototype:generate:files. 
         * Please do not change!
         */
        $this->setName('prototype:generate:twig:element:read')
                ->setDescription('Generate twig element read template.')
                ->addArgument('entity', InputArgument::REQUIRED, 'Insert entity class name')
                ->addArgument('rootFolder', InputArgument::OPTIONAL, 'Insert rootFolder')
                ->addOption('withAssociated', null, InputOption::VALUE_NONE, 'Insert associated param');
        

    }
    
     protected function execute(InputInterface $input, OutputInterface $output) {

        $command = $this->getApplication()->find("prototype:generate:twig");

        $arguments = array(
            "--entity" => $input->getArgument("entity"),
            "--rootFolder" => $input->getArgument("rootFolder"),
            "--viewType" => "Element",
            "--templatePath" => "CorePrototypeBundle:Command:element.read.template.twig",
            "--fileName" => "read.html.twig",
            "--withAssociated" => $input->getOption("withAssociated"),
        );
        $inputCommand = new ArrayInput($arguments);
        $returnCode = $command->run($inputCommand, $output);
    }

}
