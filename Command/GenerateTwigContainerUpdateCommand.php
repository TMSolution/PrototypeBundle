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


use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Application;


/**
 * GridConfigCommand generates widget class and his template.
 * @author Mariusz Piela <mariuszpiela@gmail.com>
 */
class GenerateTwigContainerUpdateCommand extends ContainerAwareCommand  
{

    protected function configure()
    {
        $this->setName('prototype:generate:twig:container:update')
                ->setDescription('Generate container twig for update action.')
                ->addArgument('entity', InputArgument::REQUIRED, 'Insert config bundle name or entity path')
                ->addArgument('rootFolder', InputArgument::OPTIONAL, 'Insert rootFolder')
                ->addOption("withAssociated", null, InputOption::VALUE_NONE, "Insert associated param");
    
        
        
    }
    
     protected function execute(InputInterface $input, OutputInterface $output) {

        $command = $this->getApplication()->find("prototype:generate:twig");

        $arguments = array(
            "--entity" => $input->getArgument("entity"),
            "--rootFolder" => $input->getArgument("rootFolder"),
            "--viewType" => "Container",
            "--templatePath" => "CorePrototypeBundle:Command:container.update.template.twig",
            "--fileName" => "update.html.twig",
            "--withAssociated" => $input->getOption("withAssociated"),
        );
        $inputCommand = new ArrayInput($arguments);
        $returnCode = $command->run($inputCommand, $output);
    }

}
