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
use Symfony\Component\Console\Input\ArrayInput;
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
                ->addArgument('configBundle', InputArgument::REQUIRED, 'Insert configuration Bundle')
                ->addArgument('entity', InputArgument::REQUIRED, 'Insert entity path')
                ->addArgument('tag', InputArgument::REQUIRED, 'Insert tagname (example:prototype.config,prototype.formtype)')
                ->addArgument('route', InputArgument::REQUIRED, 'Insert route parameter (example.: core_prototype_ )')
                ->addArgument('parent', InputArgument::OPTIONAL, 'Insert parent parameter')
                ->addOption('withAssociated', null, InputOption::VALUE_NONE, 'Insert associated param');
    }

    protected function getConfigFilePath($manager, $input)
    {
        $bundle = $this->getApplication()->getKernel()->getBundle($input->getArgument('configBundle'));
        $bundleMetadata = $manager->getBundleMetadata($bundle);
        return str_replace('/', DIRECTORY_SEPARATOR, $bundleMetadata->getPath()) . DIRECTORY_SEPARATOR . $bundleMetadata->getNamespace() . DIRECTORY_SEPARATOR . 'Resources/config/services.yml';
    }

    protected function getClassPath($entity, $manager)
    {
        $classPath = $manager->getClassMetadata($entity)->getPath();
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

    protected function readYml($configFullPath)
    {
        try {
            if (file_exists($configFullPath)) {
                $yaml = new Parser();
                $yamlArr = $yaml->parse(file_get_contents($configFullPath));
            } else {
                $yamlArr = ['parameters' => [], 'services' => []];
            }

            return $yamlArr;
        } catch (\Exception $e) {
            throw new \Exception('Error reading yml file.');
        }
    }

    protected function writeYml($fileName, $yamlArr, $output)
    {

        $yaml = new Dumper();
        $yamlData = $yaml->dump($yamlArr, 4, 0, false, true);

        //die($yamlData);
        file_put_contents($fileName, str_replace("'@service_container'", "@service_container", $yamlData));
        $output->writeln("Services configuration file <info>" . $fileName . "</info> generated.");
    }

    protected function checkKeyExist($yamlArr, $key, $output)
    {
        $output->writeln("Check key: <info>" . $key . "</info>.");
        return array_key_exists($key, $yamlArr['services']);
    }

    protected function addService($output, &$yamlArr, $entity, $tag = '', $route = '', $parent = '', $associatedName = null)
    {
        $serviceName = $this->createServiceName($entity, $tag, $associatedName);
        $className = $this->createClassName($entity, $tag, $associatedName);
        if (!$this->checkKeyExist($yamlArr, $serviceName, $output)) {
            switch ($tag) {
                //case 'prototype.config':
                case 'prototype.gridconfig':
                    $this->addGridConfigService($yamlArr, $serviceName, $className, $entity, $tag, $route, $parent);
                    break;
                case 'prototype.formtype':
                    $this->addFormTypeService($yamlArr, $serviceName, $className, $entity, $tag, $route, $parent);
                    break;
            }
        } else {
            $output->writeln("Service <comment>" . $serviceName . "</comment> already exist.");
        }
    }

    protected function addGridConfigService(&$yamlArr, $serviceName, $class, $entity, $tag = '', $route = '', $parent = '')
    {
        $yamlArr['services'][$serviceName] = [
            'class' => "'$class'",
            'arguments' => ["@service_container"],
            'tags' => [['name' => "'$tag'", 'route' => "'$route'", 'entity' => "'$entity'", 'parent' => "'$parent'"]]
        ];
    }

    protected function addFormTypeService(&$yamlArr, $serviceName, $class, $entity, $tag = '', $route = '', $parent = '')
    {
        $yamlArr['services'][$serviceName] = [
            'class' => "'$class'",
            'arguments' => [],
            'tags' => [['name' => "'$tag'", 'route' => "'$route'", 'entity' => "'$entity'", 'parent' => "'$parent'"]]
        ];
    }

    protected function repairApostrophes(&$yamlArr)
    {
        foreach ($yamlArr['services'] as $key => $value) {

            foreach ($value as $key2 => $value2) {

                if ($key2 == 'class' && substr($value2, 0, 1) != "'") {
                    $yamlArr['services'][$key][$key2] = "'" . $value2 . "'";
                }
                if ($key2 == 'tags' && is_array($value2) && substr($value2[0]['entity'], 0, 1) != "'") {
                    $yamlArr['services'][$key][$key2][0]['entity'] = "'" . $value2[0]['entity'] . "'";
                }
            }
        }
    }

    protected function createServiceName($entity, $tag, $associationName = null)
    {

        $tagArr = explode('.', $tag);
        unset($tagArr[0]);
        $tagName = implode('.', $tagArr);

        switch ($tag) {
            //case 'prototype.config':
            case 'prototype.gridconfig':
            case 'prototype.formtype':
                $serviceName = str_replace('\\', '.', str_replace('bundle\\entity', '', strtolower($entity)));
                $serviceName .= '.' . strtolower($tagName);
                if ($associationName) {
                    $original = explode('.', $serviceName);
                    $inserted = [strtolower($associationName)];
                    array_splice($original, 1, 0, $inserted);
                    $serviceName = implode('.', $original);
                }
                break;
        }

        return $serviceName;
    }

    protected function createClassName($entity, $tag, $associationName = null)
    {

        switch ($tag) {
            //case 'prototype.config':
            case 'prototype.gridconfig':
                $className = str_replace('\\Entity', '\\Config', $entity) . '\\GridConfig';
                if ($associationName) {
                    $className = str_replace('\\Config\\', '\\Config\\' . $associationName . '\\', $className);
                }
                break;
            case 'prototype.formtype':
                $className = str_replace('\\Entity', '\\Config', $entity) . '\\FormType';
                if ($associationName) {
                    $className = str_replace('\\Config\\', '\\Config\\' . $associationName . '\\', $className);
                }
                break;
        }

        return $className;
    }

    protected function runAssociatedObjectsRecursively($fieldsInfo, &$yamlArr, $input, $output)
    {
        $arr = explode('\\', $input->getArgument('entity'));
        $parentEntity = array_pop($arr);

        $associations = [];
        foreach ($fieldsInfo as $key => $value) {

            $associationTypes = ["OneToMany", "ManyToMany"];
            $field = $fieldsInfo[$key];
            if (array_key_exists("association", $field) && in_array($field["association"], $associationTypes)) {
                $this->addService($output, $yamlArr, $value['object_name'], $input->getArgument('tag'), $input->getArgument('route'), $input->getArgument('parent'), $parentEntity);
            }
        }
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {


        $manager = new DisconnectedMetadataFactory($this->getContainer()->get('doctrine'));
        $configFullPath = $this->getConfigFilePath($manager, $input);
        $entity = $input->getArgument('entity');
        $tag = $input->getArgument('tag');
        $route = $input->getArgument('route');
        $parent = $input->getArgument('parent');
        $serviceName = '';

        //Read yaml file to array $yamlArr
        if ($entity) {
            $model = $this->getContainer()->get("model_factory")->getModel($entity);
            $fieldsInfo = $model->getFieldsInfo();
            $classPath = $this->getClassPath($entity, $manager);
            $entityReflection = new ReflectionClass($entity);
            $yamlArr = $this->readYml($configFullPath);
        }

        //Repair apotrophes on class path
        $this->repairApostrophes($yamlArr);

        //add service
        if ($configFullPath && $yamlArr) {
            $this->addService($output, $yamlArr, $entity, $tag, $route, $parent);
        }


        //generate assoc services
        if (true === $input->getOption('withAssociated')) {
            $this->runAssociatedObjectsRecursively($fieldsInfo, $yamlArr, $input, $output);
        }

        //onetomany manytomany
        $this->writeYml($configFullPath, $yamlArr, $output);
    }

}
