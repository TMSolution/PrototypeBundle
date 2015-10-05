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
use Core\PrototypeBundle\Component\Yaml\Parser;
use Core\PrototypeBundle\Component\Yaml\Dumper;
use ReflectionClass;
use LogicException;
use UnexpectedValueException;

/**
 * GridConfigCommand generates widget class and his template.
 * @author Mariusz Piela <mariuszpiela@gmail.com>
 */
class GenerateServicesConfigurationCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this->setName('prototype:generate:services')
                ->setDescription('Generate services configuration in bundle services.yml')
                ->addArgument('name', InputArgument::REQUIRED, 'Insert bundle name or entity path');
    }

    protected function getClassPath($entityName, $manager)
    {
        $classPath = $manager->getClassMetadata($entityName)->getPath();
        return $classPath;
    }

    protected function createDirectory($classPath, $entityNamespace)
    {
        $directory = str_replace("/", DIRECTORY_SEPARATOR, str_replace("\\", DIRECTORY_SEPARATOR, ($classPath . '/' . $entityNamespace)));
        $directory = $this->replaceLast("Entity", "Resources" . DIRECTORY_SEPARATOR . "config", $directory);

        if (is_dir($directory) == false) {
            if (mkdir($directory, 0777, true) == false) {
                throw new UnexpectedValueException("Creating directory failed: " . $directory);
            }
        }

        return $directory;
    }

    protected function isFileNameBusy($fileName)
    {
        if (file_exists($fileName) == true) {
            throw new LogicException("File " . $fileName . " exists!");
        }
        return false;
    }

    protected function replaceLast($search, $replace, $subject)
    {
        $position = strrpos($subject, $search);
        if ($position !== false) {
            $subject = \substr_replace($subject, $replace, $position, strlen($search));
        }
        return $subject;
    }

    protected function readYml($fileName)
    {
        try {
            $yaml = new Parser();
            return $yaml->parse(file_get_contents($fileName));
        } catch (\Exception $e) {
            throw new \Exception('Error reading yml file.');
        }
    }
    
    protected function writeYml($fileName, $yamlArr, $output)
    {
        $yaml = new Dumper();
        $yamlData = $yaml->dump($yamlArr, 4, 0, false, true);

        
        file_put_contents($fileName, $yamlData);
        $output->writeln("Services configuration file <info>" . $fileName . "</info> generated.");
    }

    protected function checkKeyExist($yamlArr, $key, $output)
    {
        $output->writeln("Check key: <info>" . $key . "</info>.");
        return array_key_exists($key, $yamlArr['services']);
    }

    protected function addService(&$yamlArr, $key, $class, $argumentsArray, $tags)
    {
        $yamlArr['services'][$key] = [
            'class' => "'$class'",
            'arguments' => $argumentsArray,
            'tags' => $tags
        ];
    }

    protected function createServiceName($entity, $serviceType, $associationName = null)
    {
        $serviceName = str_replace('\\', '.', str_replace('bundle\\entity', '', strtolower($entity)));
        if ($associationName) {
            $serviceName .= '.' . strtolower($associationName);
        }
        $serviceName .= '.' . strtolower($serviceType);
        return $serviceName;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {


        $manager = new DisconnectedMetadataFactory($this->getContainer()->get('doctrine'));
        $entities = [];

        try {
            $bundle = $this->getApplication()->getKernel()->getBundle($input->getArgument('name'));
            $output->writeln(sprintf('Generating services.yml for "<info>%s</info>"', $bundle->getName()));
            $bundleMetadata = $manager->getBundleMetadata($bundle);
            foreach ($bundleMetadata->getMetadata() as $metadata) {
                $entities[] = $metadata->getName();
            }
        } catch (\InvalidArgumentException $e) {
            try {
                $model = $this->getContainer()->get("model_factory")->getModel($input->getArgument('name'));
                $metadata = $model->getMetadata();
                $entities[] = $metadata->getName();
            } catch (\Exception $e) {
                $output->writeln("<error>Argument \"" . $input->getArgument('name') . "\" not exist.</error>");
                exit;
            }
        }

        $twigEntities = [];
        $fileName = null;
        $yamlArr = null;

        if ($entities && $entities[0]) {
            $classPath = $this->getClassPath($entities[0], $manager);
            $entityReflection = new ReflectionClass($entities[0]);
            $directory = $this->createDirectory($classPath, $entityReflection->getNamespaceName());
  
            $fileName = $directory . DIRECTORY_SEPARATOR . "services" . ".yml";

            if (file_exists($fileName)) {
                $yamlArr = $this->readYml($fileName);
            }
            else{
                $yamlArr = ['parameters'=>[],'services'=>[]];
            }
        }
        
  
        foreach ($entities as $entityName) {

            if ($fileName && $yamlArr) {

                $serviceName = $this->createServiceName($entityName, 'gridconfig');
                if (!$this->checkKeyExist($yamlArr, $serviceName, $output)) {
                    $this->addService($yamlArr, $serviceName, $entityName, [ '@service_container'], [['name' => "'prototype.gridconfig'", 'route' => "'core_prototype_'", 'entity' => "'$entityName'"]]);
                } else {
                    
                    
                    $output->writeln("Service <comment>" . $serviceName . "</comment> already exist.");
                }

                $serviceName = $this->createServiceName($entityName, 'associationgridconfig');
                if (!$this->checkKeyExist($yamlArr, $serviceName, $output)) {
                    $this->addService($yamlArr, $serviceName, $entityName, [ '@service_container'], [['name' => "'prototype.gridconfig'", 'route' => "'core_prototype_'", 'entity' => "'$entityName'"]]);
                } else {
                    $output->writeln("Service <comment>" . $serviceName . "</comment> already exist.");
                }
            }
        }

        $this->writeYml($fileName, $yamlArr, $output);
        
        
    }

    

}
