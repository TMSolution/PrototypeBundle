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

/**
 * GenerateServicesConfigurationCommand generates services configuration.
 * @author Mariusz Piela <mariuszpiela@gmail.com>
 * @author Jacek Łoziński <jacek.lozinski@tmsolution.pl>
 */
class GenerateServicesConfigurationCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this->setName('prototype:generate:services')
                ->setDescription('Generate services configuration in bundle prototype.services.yml')
                ->addArgument('configBundle', InputArgument::REQUIRED, 'Insert configuration Bundle')
                ->addArgument('applicationName', InputArgument::REQUIRED, 'Application name (ex. cco_callcenter,...)')
                ->addArgument('rootSpace', InputArgument::REQUIRED, 'Insert rootSpace')
                ->addArgument('entity', InputArgument::REQUIRED, 'Insert entity path')
                ->addArgument('tag', InputArgument::REQUIRED, 'Insert tagname (example:prototype.config,prototype.formtype)')
                ->addArgument('route', InputArgument::REQUIRED, 'Insert route parameter (example.: core_prototype_ )')
                ->addArgument('parentEntity', InputArgument::OPTIONAL, 'Insert parentEntity parameter')
                ->addOption('withAssociated', null, InputOption::VALUE_NONE, 'Insert associated param');
    }

    protected function getConfigFilePath($manager, $input)
    {
        $bundle = $this->getApplication()->getKernel()->getBundle($input->getArgument('configBundle'));
        $bundleMetadata = $manager->getBundleMetadata($bundle);
        return str_replace('/', DIRECTORY_SEPARATOR, $bundleMetadata->getPath()) . DIRECTORY_SEPARATOR . $bundleMetadata->getNamespace() . DIRECTORY_SEPARATOR . 'Resources/config/prototype.services.yml';
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
                if ($yamlArr === NULL) {
                    $yamlArr = ['parameters' => [], 'services' => []];
                }
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

    protected function checkServicesKeyExist($yamlArr, $key, $output)
    {
        $output->writeln("Check service key: <info>" . $key . "</info>.");
        if (is_array($yamlArr['services'])) {
            return array_key_exists($key, $yamlArr['services']);
        }
        return false;
    }

    protected function checkParametersKeyExist($yamlArr, $key, $output)
    {
        $output->writeln("Check parameters key: <info>" . $key . "</info>.");
        if (is_array($yamlArr['parameters'])) {
            return array_key_exists($key, $yamlArr['parameters']);
        }
    }

    protected function addService($output, &$yamlArr, $entity, $configBundleName, $tag = '', $route = '', $parentEntity = '', $associatedName = null, $parameterName = '', $rootSpace = '')
    {
        $serviceName = $this->createServiceName($entity, $tag, $associatedName, $rootSpace);
        $className = $this->createClassName($entity, $tag, $configBundleName, $associatedName, $rootSpace, $configBundleName);
        if (!$this->checkServicesKeyExist($yamlArr, $serviceName, $output)) {
            switch ($tag) {
                case 'prototype.config':
                    $this->addConfigService($yamlArr, $serviceName, $className, $entity, $tag, $route, $parentEntity, $parameterName);
                    break;
                case 'prototype.gridconfig':
                    $this->addGridConfigService($yamlArr, $serviceName, $className, $entity, $tag, $route, $parentEntity);
                    break;
                case 'prototype.listconfig':
                    $this->addListConfigService($yamlArr, $serviceName, $className, $entity, $tag, $route, $parentEntity);
                case 'prototype.viewconfig':
                    $this->addViewConfigService($yamlArr, $serviceName, $className, $entity, $tag, $route, $parentEntity);
                    break;
                case 'prototype.formtype':
                    $this->addFormTypeService($yamlArr, $serviceName, $className, $entity, $tag, $route, $parentEntity);
                    break;
            }
        } else {
            $output->writeln("Service <comment>" . $serviceName . "</comment> already exist.");
        }
    }

    protected function addParameters(&$yamlArr, $entity, $tag, $bundleName, $rootSpace, $objectName, $output, $associated = false, $configBundleName)
    {

        $configBundleName = str_replace('\\', '', $configBundleName);
        $parametersName = '';
        if (!$this->checkParametersKeyExist($yamlArr, $parametersName, $output)) {

            switch ($tag) {
                case 'prototype.config':


                    if ($associated) {
                        $parametersName = $this->createParametersName($entity, $tag, $rootSpace);
                        $yamlArr['parameters'][$parametersName] = [
                            'twig_element_read' => /* $bundleName */$configBundleName . ':' . $rootSpace . '\\' . $objectName . '\\Element:read.html.twig',
                            'twig_element_create' => /* $bundleName */$configBundleName . ':' . $rootSpace . '\\' . $objectName . '\\Element:create.html.twig',
                            'twig_element_update' => /* $bundleName */$configBundleName . ':' . $rootSpace . '\\' . $objectName . '\\Element:update.html.twig',
                            'twig_element_view' => /* $bundleName */$configBundleName . ':' . $rootSpace . '\\' . $objectName . '\\Element:view.html.twig',
                            'twig_element_list' => /* $bundleName */$configBundleName . ':' . $rootSpace . '\\' . $objectName . '\\Element:list.html.twig'
                        ];
                    } else {
                        $parametersName = $this->createParametersName($entity, $tag, $rootSpace);
                        $yamlArr['parameters'][$parametersName] = [
                            'twig_container_view' => /* $bundleName */$configBundleName . ':' . $rootSpace . '\\' . $objectName . '\\Container:view.html.twig',
                            'twig_container_read' => /* $bundleName */$configBundleName . ':' . $rootSpace . '\\' . $objectName . '\\Container:read.html.twig',
                            'twig_container_create' => /* $bundleName */$configBundleName . ':' . $rootSpace . '\\' . $objectName . '\\Container:create.html.twig',
                            'twig_container_update' => /* $bundleName */$configBundleName . ':' . $rootSpace . '\\' . $objectName . '\\Container:update.html.twig',
                            'twig_container_grid' => /* $bundleName */$configBundleName . ':' . $rootSpace . '\\' . $objectName . '\\Container:grid.html.twig',
                            'twig_container_list' => /* $bundleName */$configBundleName . ':' . $rootSpace . '\\' . $objectName . '\\Container:list.html.twig',
                            'twig_element_view' => /* $bundleName */$configBundleName . ':' . $rootSpace . '\\' . $objectName . '\\Element:view.html.twig',
                            'twig_element_read' => /* $bundleName */$configBundleName . ':' . $rootSpace . '\\' . $objectName . '\\Element:read.html.twig',
                            'twig_element_create' => /* $bundleName */$configBundleName . ':' . $rootSpace . '\\' . $objectName . '\\Element:create.html.twig',
                            'twig_element_update' => /* $bundleName */$configBundleName . ':' . $rootSpace . '\\' . $objectName . '\\Element:update.html.twig',
                            'twig_element_list' => /* $bundleName */$configBundleName . ':' . $rootSpace . '\\' . $objectName . '\\Element:list.html.twig'
                        ];
                    }
                    break;
                case 'prototype.formtype':
                    break;
            }
        } else {
            $output->writeln("Twig parameters for <comment>" . $parametersName . "</comment> already exist.");
        }

        return $parametersName;
    }

    protected function addGridConfigService(&$yamlArr, $serviceName, $class, $entity, $tag = '', $route = '', $parentEntity = '')
    {
        $yamlArr['services'][$serviceName] = [
            'class' => "'$class'",
            'arguments' => ["@service_container"],
            'tags' => [['name' => "'$tag'", 'route' => "'$route'", 'entity' => "'$entity'", 'parentEntity' => "'$parentEntity'"]]
        ];
    }

    protected function addListConfigService(&$yamlArr, $serviceName, $class, $entity, $tag = '', $route = '', $parentEntity = '')
    {
        $yamlArr['services'][$serviceName] = [
            'class' => "'$class'",
            'arguments' => ["@service_container"],
            'tags' => [['name' => "'$tag'", 'route' => "'$route'", 'entity' => "'$entity'", 'parentEntity' => "'$parentEntity'"]]
        ];
    }

    protected function addViewConfigService(&$yamlArr, $serviceName, $class, $entity, $tag = '', $route = '', $parentEntity = '')
    {
        $yamlArr['services'][$serviceName] = [
            'class' => "'$class'",
            'arguments' => ["@service_container"],
            'tags' => [['name' => "'$tag'", 'route' => "'$route'", 'entity' => "'$entity'", 'parentEntity' => "'$parentEntity'"]]
        ];
    }

    protected function addConfigService(&$yamlArr, $serviceName, $class, $entity, $tag = '', $route = '', $parentEntity = '', $parameterName = '')
    {
        $yamlArr['services'][$serviceName] = [
            'class' => "'$class'",
            'arguments' => ["%prototype_config_params%", "%$parameterName%"],
            'tags' => [['name' => "'$tag'", 'route' => "'$route'", 'entity' => "'$entity'", 'parentEntity' => "'$parentEntity'"]]
        ];
    }

    protected function addFormTypeService(&$yamlArr, $serviceName, $class, $entity, $tag = '', $route = '', $parentEntity = '')
    {
        $yamlArr['services'][$serviceName] = [
            'class' => "'$class'",
            'arguments' => [],
            'tags' => [['name' => "'$tag'", 'route' => "'$route'", 'entity' => "'$entity'", 'parentEntity' => "'$parentEntity'"]]
        ];
    }

    protected function repairApostrophes(&$yamlArr)
    {

        if (is_array($yamlArr['services'])) {
            foreach ($yamlArr['services'] as $key => $value) {

                foreach ($value as $key2 => $value2) {

                    if ($key2 == 'class' && substr($value2, 0, 1) != "'") {
                        $yamlArr['services'][$key][$key2] = "'" . $value2 . "'";
                    }
                    if ($key2 == 'tags' && is_array($value2) && substr($value2[0]['entity'], 0, 1) != "'") {
                        $yamlArr['services'][$key][$key2][0]['entity'] = "'" . $value2[0]['entity'] . "'";

                        if (array_key_exists('parentEntity', $value2[0])) {
                            $yamlArr['services'][$key][$key2][0]['parentEntity'] = "'" . $value2[0]['parentEntity'] . "'";
                        }
                    }
                }
            }
        }
    }

    protected function createServiceName($entity, $tag, $associationName = null, $rootSpace = null)
    {

        $tagArr = explode('.', $tag);
        unset($tagArr[0]);
        $tagName = implode('.', $tagArr);

        switch ($tag) {
            case 'prototype.config':
            case 'prototype.gridconfig':
            case 'prototype.listconfig':
            case 'prototype.viewconfig':
            case 'prototype.formtype':
                $serviceName = str_replace('\\', '.', str_replace('bundle\\entity', '', strtolower($entity)));
                $serviceName .= '.' . strtolower($tagName);


                if ($associationName) {
                    $original = explode('.', $serviceName);

                    if ($rootSpace && $rootSpace != $associationName) {
                        $inserted = [strtolower($rootSpace . '.' . $associationName)];
                    } else {
                        $inserted = [strtolower($associationName)];
                    }
                    array_splice($original, 2, 0, $inserted);
                    $serviceName = implode('.', $original);
                }

                break;
        }

        return $serviceName;
    }

    protected function createParametersName($entity, $tag, $associationName = null)
    {

        $tagArr = explode('.', $tag);
        unset($tagArr[0]);
        $tagName = implode('.', $tagArr);

        switch ($tag) {
            case 'prototype.config':
                $parametersName = 'parameters.' . str_replace('\\', '.', str_replace('bundle\\entity', '', strtolower($entity)));
                $parametersName .= '.' . strtolower($tagName);
                if ($associationName) {
                    $original = explode('.', $parametersName);
                    $inserted = [strtolower(str_replace('\\', '.', $associationName))];
                    array_splice($original, 3, 0, $inserted);
                    $parametersName = implode('.', $original);
                }
                break;
        }

        return str_replace('.config', '', $parametersName);
    }

    protected function createClassName($entity, $tag, $configBundleName, $associationName = null, $rootSpace = null)
    {

        switch ($tag) {
            case 'prototype.config':
                $className = 'Core\\PrototypeBundle\\Service\\Config';
                break;
            case 'prototype.gridconfig':

                $className = str_replace('\\Entity', '\\Config', $entity) . '\\GridConfig';
                $classNamePrefix = substr($className, 0, strpos($className, 'Bundle', 0) + 6);
                $className = str_replace($classNamePrefix, $configBundleName, $className);

                if ($associationName) {

                    if ($rootSpace && $rootSpace != $associationName) {
                        $className = str_replace('\\Config\\', '\\Config\\' . $rootSpace . '\\' . $associationName . '\\', $className);
                    } else {
                        $className = str_replace('\\Config\\', '\\Config\\' . $associationName . '\\', $className);
                    }
                }
                break;
            case 'prototype.listconfig':

                $className = str_replace('\\Entity', '\\Config', $entity) . '\\ListConfig';
                $classNamePrefix = substr($className, 0, strpos($className, 'Bundle', 0) + 6);
                $className = str_replace($classNamePrefix, $configBundleName, $className);

                if ($associationName) {

                    if ($rootSpace && $rootSpace != $associationName) {
                        $className = str_replace('\\Config\\', '\\Config\\' . $rootSpace . '\\' . $associationName . '\\', $className);
                    } else {
                        $className = str_replace('\\Config\\', '\\Config\\' . $associationName . '\\', $className);
                    }
                }
                break;
            case 'prototype.viewconfig':

                $className = str_replace('\\Entity', '\\Config', $entity) . '\\ViewConfig';
                $classNamePrefix = substr($className, 0, strpos($className, 'Bundle', 0) + 6);
                $className = str_replace($classNamePrefix, $configBundleName, $className);

                if ($associationName) {

                    if ($rootSpace && $rootSpace != $associationName) {
                        $className = str_replace('\\Config\\', '\\Config\\' . $rootSpace . '\\' . $associationName . '\\', $className);
                    } else {
                        $className = str_replace('\\Config\\', '\\Config\\' . $associationName . '\\', $className);
                    }
                }
                break;
            case 'prototype.formtype':

                $className = str_replace('\\Entity', '\\Config', $entity) . '\\FormType';
                $classNamePrefix = substr($className, 0, strpos($className, 'Bundle', 0) + 6);
                $className = str_replace($classNamePrefix, $configBundleName, $className);


                if ($rootSpace && $rootSpace != $associationName) {
                    $className = str_replace('\\Config\\', '\\Config\\' . $rootSpace . '\\' . $associationName . '\\', $className);
                } else {
                    $className = str_replace('\\Config\\', '\\Config\\' . $associationName . '\\', $className);
                }
                break;
        }

        return $className;
    }

    protected function runAssociatedObjectsRecursively($fieldsInfo, &$yamlArr, $input, $output, $rootSpace, $objectName, $bundleName, $entity, $configBundleName, $routeName)
    {
        $associations = [];
        foreach ($fieldsInfo as $key => $value) {

            $associationTypes = ["OneToMany", "ManyToMany"];
            $field = $fieldsInfo[$key];
            if (array_key_exists("association", $field) && in_array($field["association"], $associationTypes)) {
                $arr = explode('\\', $input->getArgument('entity'));
                $rootLast = array_pop($arr);
                $arr = explode('\\', $value['object_name']);
                $last = array_pop($arr);
                $parameterName = $this->addParameters($yamlArr, $value['object_name'], $input->getArgument('tag'), $bundleName, $rootSpace . DIRECTORY_SEPARATOR . $objectName, $last, $output, true, $configBundleName);
                $this->addService($output, $yamlArr, $value['object_name'], $configBundleName, $input->getArgument('tag'), /* 'core_prototype_associationcontroller_' */ $routeName . 'association_', $entity, $rootLast, $parameterName, $rootSpace);
            }
        }
    }

    protected function getConfigBundleName($input, $output, $manager)
    {



        try {

            $configBundle = $this->getApplication()->getKernel()->getBundle($input->getArgument('configBundle'));


            $bundleClass = get_class($configBundle);
            $configBundleName = substr($bundleClass, 0, strpos($bundleClass, 'Bundle', 0) + 6);
        } catch (\InvalidArgumentException $e) {
            try {
                $class = get_class($input->getArgument('configBundle'));
                $configBundleName = substr($class, 0, strpos($class, 'Bundle', 0) + 6);
            } catch (\Exception $e) {
                $output->writeln("<error>Argument configBundle:\"" . $input->getArgument('configBundle') . "\" not exist.</error>");
                exit;
            }
        }


        if (!$configBundleName) {
            $output->writeln("<error>Argument configEntityName not exist.</error>");
            exit;
        }

        return $configBundleName;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {


        $manager = new DisconnectedMetadataFactory($this->getContainer()->get('doctrine'));
        $configFullPath = $this->getConfigFilePath($manager, $input);
        $entity = $input->getArgument('entity');
        $tag = $input->getArgument('tag');
        $route = $input->getArgument('route');
        $parentEntity = $input->getArgument('parentEntity');

        $rootSpace = $input->getArgument('rootSpace');

        //configNameSpace
        $configBundleName = $this->getConfigBundleName($input, $output, $manager);


        $serviceName = '';

        //Read yaml file to array $yamlArr
        if ($entity) {
            $model = $this->getContainer()->get("model_factory")->getModel($entity);
            $metadata = $model->getMetadata();
            $bundleName = str_replace('\\', '', str_replace('\Entity', null, $metadata->namespace));
            $fieldsInfo = $model->getFieldsInfo();
            $classPath = $this->getClassPath($entity, $manager);
            $entityReflection = new ReflectionClass($entity);
            $objectName = $entityReflection->getShortName();
            $yamlArr = $this->readYml($configFullPath);
            
            
            $applicationName=$input->getArgument('applicationName');

            $routeName = strtolower($applicationName . '_' . $rootSpace . '_');

            //Repair apotrophes on class path
            $this->repairApostrophes($yamlArr);

            //add service
            if ($configFullPath && $yamlArr) {
                $parameterName = $this->addParameters($yamlArr, $entity, $tag, $bundleName, $rootSpace, $objectName, $output, false, $configBundleName);
                $this->addService($output, $yamlArr, $entity, $configBundleName, $tag, $routeName, $parentEntity, $rootSpace, $parameterName, $rootSpace);
            }


            //generate assoc services
            if (true === $input->getOption('withAssociated')) {
                $this->runAssociatedObjectsRecursively($fieldsInfo, $yamlArr, $input, $output, $rootSpace, $objectName, $bundleName, $entity, $configBundleName, $routeName);
            }


            $this->writeYml($configFullPath, $yamlArr, $output);
        }
    }

}
