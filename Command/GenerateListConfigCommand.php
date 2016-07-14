<?php

/**
 * 
 * Copyright (c) 2014, TMSolution
 * All rights reserved.
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace Core\PrototypeBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\Bundle\DoctrineBundle\Mapping\DisconnectedMetadataFactory;
use ReflectionClass;
use LogicException;
use UnexpectedValueException;
use Core\PrototypeBundle\Generator\ListConfigGenerator as ListConfigGenerator;

/**
 * ListConfigCommand generates widget class and his template.
 * @author Mariusz Piela <mariuszpiela@gmail.com>
 */
class GenerateListConfigCommand extends AbstractGenerateCommand {

    protected function configure() {
        
        parent::configure();
        
        $this->setName('prototype:generate:listconfig')
            ->setDescription("Generated ListConfig");
            
 
    }

     protected function execute(InputInterface $input, OutputInterface $output) {

        $generator = new ListConfigGenerator(
                $this->getContainer(), $input->getOption('entity'), $input->getOption('templatePath'), $input->getOption('fileName'), $input->getOption('rootFolder'), $input->getOption('withAssociated')
        );
        
        
        $fileName = $generator->generate();
        $output->writeln("$fileName created");
    }

}
