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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\Bundle\DoctrineBundle\Mapping\DisconnectedMetadataFactory;
use Core\PrototypeBundle\Component\Yaml\Parser;
use Core\PrototypeBundle\Component\Yaml\Dumper;
//use Symfony\Component\Console\Input\ArrayInput;
use ReflectionClass;
use LogicException;
use UnexpectedValueException;
use Core\PrototypeBundle\Generator\ParametersGenerator as ParametersGenerator;

/**
 * GenerateServicesConfigurationCommand generates services configuration.
 * @author Mariusz Piela <mariuszpiela@gmail.com>
 * @author Jacek Łoziński <jacek.lozinski@tmsolution.pl>
 */
class GenerateParametersCommand extends ContainerAwareCommand {

    protected function configure() {
        $this->setName('prototype:generate:parameters')
                ->setDescription('Generate services configuration in bundle prototype.services.yml')
                ->addOption('entity', 'ent', InputOption::VALUE_REQUIRED, 'Insert configuration Bundle')
                ->addOption('rootFolder', 'rf', InputOption::VALUE_REQUIRED, 'Insert configuration Bundle')
                ->addOption('parentEntity', 'pe', InputOption::VALUE_OPTIONAL, 'Insert parentEntity parameter')
                ->addOption('withAssociated', 'wa', InputOption::VALUE_OPTIONAL, 'Insert associated param');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        $generator = new ParametersGenerator(
                $this->getContainer(), $input->getOption('entity'),$input->getOption('rootFolder'),$input->getOption('parentEntity'), $input->getOption('withAssociated')
        );

        $fileName = $generator->generate();
        $output->writeln("$fileName created");
    }

}
