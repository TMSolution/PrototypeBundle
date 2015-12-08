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
class GenerateTwigElementUpdateCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        /*
         * type "OPTIONAL" for the parameter "rootFolder" was chosen because of compatibility with the global command prototype:generate:files. 
         * Please do not change!
         */
        $this->setName('prototype:generate:twig:element:update')
                ->setDescription('Generate twig element update template.')
                ->addArgument('configBundle', InputArgument::REQUIRED, 'Insert config bundle name or entity path')
                ->addArgument('entity', InputArgument::OPTIONAL, 'Insert entity class name')
                ->addArgument('rootFolder', InputArgument::OPTIONAL, 'Insert rootFolder')
                ->addOption('withAssociated', null, InputOption::VALUE_NONE, 'Insert associated param');
        
    }

    protected function getEntityName($input)
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $entityName = str_replace('/', '\\', $input->getArgument('entity'));
        if (($position = strpos($entityName, ':')) !== false) {
            $entityName = $doctrine->getAliasNamespace(substr($entityName, 0, $position)) . '\\' . substr($entityName, $position + 1);
        }

        return $entityName;
    }

    protected function getClassPath($entityName)
    {
        $manager = new DisconnectedMetadataFactory($this->getContainer()->get('doctrine'));
        $classPath = $manager->getClassMetadata($entityName)->getPath();
        return $classPath;
    }

    protected function getGridConfigNamespaceName($entityName)
    {

        $entityNameArr = explode("\\", str_replace("Entity", "Grid", $entityName));
        unset($entityNameArr[count($entityNameArr) - 1]);
        return implode("\\", $entityNameArr);
    }

    protected function createDirectory($classPath, $entityNamespace, $objectName, $rootFolder)
    {


        $directory = str_replace("\\", DIRECTORY_SEPARATOR, ($classPath . "\\" . $entityNamespace));
        $directory = $this->replaceLast("Entity", "Resources" . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR . $rootFolder . DIRECTORY_SEPARATOR . $objectName . DIRECTORY_SEPARATOR . "Element", $directory);

        if (is_dir($directory) == false) {
            if (mkdir($directory, 0777, true) == false) {
                throw new UnexpectedValueException("Creating directory failed: " . $directory);
            }
        }


        return $directory;
    }

    protected function calculateFileName($entityReflection)
    {

        $fileName = $this->replaceLast("Entity", "Grid", $entityReflection->getFileName());
        return $fileName;
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
    
    protected function addFile($entityName, $fieldsInfo, $rootFolder)
    {
        $classPath = $this->getClassPath($entityName);
        $entityReflection = new ReflectionClass($entityName);
        $entityNamespace = $entityReflection->getNamespaceName();
        $objectName = $entityReflection->getShortName();
        $directory = $this->createDirectory($classPath, $entityNamespace, $objectName, $rootFolder);
        $fileName = $directory . DIRECTORY_SEPARATOR . "update.html.twig";
        $this->isFileNameBusy($fileName);
        $templating = $this->getContainer()->get('templating');

        $lowerNameSpaceForTranslate = str_replace('bundle.entity', '', str_replace('\\', '.', strtolower($entityNamespace)));

        $renderedConfig = $templating->render("CorePrototypeBundle:Command:element.update.template.twig", [
            "namespace" => $entityNamespace,
            "entityName" => $entityName,
            "objectName" => $objectName,
            "fieldsInfo" => $fieldsInfo,
            "lowerNameSpaceForTranslate" => $lowerNameSpaceForTranslate
        ]);

        file_put_contents($fileName, $renderedConfig);
        return $directory;
    }

    protected function runAssociatedObjectsRecursively($fieldsInfo, $rootFolder,$objectName, $output)
    {
        $associations = [];
        foreach ($fieldsInfo as $key => $value) {

            $associationTypes = ["OneToMany", "ManyToMany"];
            $field = $fieldsInfo[$key];
            if (array_key_exists("association", $field) && in_array($field["association"], $associationTypes)) {

                $model = $this->getContainer()->get("model_factory")->getModel($value['object_name']);
                $assocObjectFieldsInfo = $model->getFieldsInfo();

                $arr = explode('\\', $value['object_name']);
                $path = array_pop($arr);
               
                //$this->addFile($value['object_name'], $rootPath . DIRECTORY_SEPARATOR . $path, $assocObjectFieldsInfo, $rootFolder, $output);
                $this->addFile($value['object_name'], $assocObjectFieldsInfo, $rootFolder.DIRECTORY_SEPARATOR.$objectName);
            }
        }
    }
    
    protected function getConfigEntityName($input,$output)
    {
        $manager = new DisconnectedMetadataFactory($this->getContainer()->get('doctrine'));
                
       
        try {

            $configBundle = $this->getApplication()->getKernel()->getBundle($input->getArgument('configBundle'));
            $configBundleMetadata = $manager->getBundleMetadata($configBundle);
            $configMetadata = $configBundleMetadata->getMetadata();
            $configEntityName = $configMetadata[0]->getName();
        } catch (\InvalidArgumentException $e) {
            try {
                $configModel = $this->getContainer()->get("model_factory")->getModel($input->getArgument('configBundle'));
                $configMetadata = $configModel->getMetadata();
                $configEntityName = $configMetadata->getName();
            } catch (\Exception $e) {
                $output->writeln("<error>Argument configBundle:\"" . $input->getArgument('configBundle') . "\" not exist.</error>");
                exit;
            }
        }

        
        if (!$configEntityName) {
            $output->writeln("<error>Argument configEntityName not exist.</error>");
            exit;
        }
        
        return $configEntityName;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $entityName = $this->getEntityName($input);
        $rootFolder = $input->getArgument('rootFolder');
        $model = $this->getContainer()->get("model_factory")->getModel($entityName);
        $fieldsInfo = $model->getFieldsInfo();
        $entityReflection = new ReflectionClass($entityName);
        $objectName = $entityReflection->getShortName();
        
        //configNameSpace
        $configEntityName=$this->getConfigEntityName($input,$output);
        
        $this->addFile($entityName, $fieldsInfo, $rootFolder);


        //generate assoc form types
        if (true === $input->getOption('withAssociated')) {

            $this->runAssociatedObjectsRecursively($fieldsInfo, $rootFolder,$objectName, $output);
        }



        $output->writeln("Twig element update generated");
    }

}
