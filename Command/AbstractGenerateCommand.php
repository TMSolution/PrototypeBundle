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
abstract class AbstractGenerateCommand extends ContainerAwareCommand {

  
    protected function configure() {

    $this
                ->addOption('entity', 'ent', InputOption::VALUE_REQUIRED, 'Full Entity Name')
                ->addOption('templatePath', 'tp', InputOption::VALUE_REQUIRED, 'Twig template Path')
                ->addOption('fileName', 'fn', InputOption::VALUE_REQUIRED, 'Insert file name for new file')
                ->addOption('rootFolder', 'rf', InputOption::VALUE_OPTIONAL, 'Insert rootFolder')
                ->addOption('withAssociated', 'wa', InputOption::VALUE_OPTIONAL, 'Insert associated param');
    }


}
