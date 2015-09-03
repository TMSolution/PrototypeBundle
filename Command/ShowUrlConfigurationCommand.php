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
class ShowUrlConfigurationCommand extends ContainerAwareCommand {

    protected function configure() {
        $this->setName('prototype:show:url:config')
                ->setDescription('Show route config. Use url name start with / (example: /panel/entity/1)')
                ->addArgument(
                        'url', InputArgument::REQUIRED, 'Insert url'
        );
    }

    protected function getRouteInfo($input, $output) {

        $output->writeln("Url configuration: ");
        $route = $this->getContainer()->get('router')->match($input->getArgument('url'));
        $output->writeln("  url: <info>" . $input->getArgument('url')."</info>");
        $output->writeln("  route: <info>" . $route["_route"]."</info>");
        $output->writeln("  controller: <info>" . $route["_controller"]."</info>");
        $output->writeln("  locale: <info>" . $route["_locale"]."</info>");
        $output->writeln("  entityName: <info>" . $route["entityName"]."</info>");
        $entityName = $this->getContainer()->get("classmapperservice")->getEntityClass($route["entityName"], $route["_locale"]);
        $output->writeln("  entityClass: <info>" . $entityName."</info>");
        
        $output->writeln("");
        $output->writeln("Services: ");
        $output->writeln("");
        $configuratorService=$this->getContainer()->get('prototype.configurator.service');
        $namesOfServices=$configuratorService->getNamesOfServices();
        $service=$configuratorService->getService($route["_route"], $entityName);
        $this->printServiceInfo("Base config(twig)",$configuratorService,$output);
        exit();
        
        $configuratorService=$this->getContainer()->get('prototype.gridbuilder.configurator.service');
        $namesOfServices=$configuratorService->getNamesOfServices();
        $service=$configuratorService->getService($route["_route"], $entityName);
        $this->printServiceInfo("Grid builder config",$configuratorService,$output);
        
        
    }
    
    
    protected function printServiceInfo($name,$service,$output)
    {
        $serviceConfig=$service->getChosen();
        $output->writeln("  ".$name.": " );
        $output->writeln("      phrase: <info>" .$serviceConfig['phrase']."</info>" );
        $output->writeln("      servicename: <comment>" .$serviceConfig['serviceid']."</comment>" );
        $output->writeln("      class: <comment>" .get_class($serviceConfig['service'])."</comment>" );
        
        dump($serviceConfig->getConfig());
        $output->writeln("" );
        
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        $this->getRouteInfo($input, $output);
        //$output->writeln("Twig:containter:read generated");
    }

}
