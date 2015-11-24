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
    
    public function getDefaultField($entityName)
    {
        $model = $this->getContainer()->get("model_factory")->getModel($entityName);
        if ($model->checkPropertyByName("name")) {
            return "name";
        } else {
            return "id";
        }
    }

    protected function addFile($entityName, $fieldsInfo, $rootFolder,$isAssociated=false)
    {
        $classPath = $this->getClassPath($entityName);
        $entityReflection = new ReflectionClass($entityName);
        $entityNamespace = $entityReflection->getNamespaceName();
        $objectName = $entityReflection->getShortName();
        $directory = $this->createDirectory($classPath, $entityNamespace, $objectName, $rootFolder);
        $fileName = $directory . DIRECTORY_SEPARATOR . "read.html.twig";
        $this->isFileNameBusy($fileName);
        $templating = $this->getContainer()->get('templating');

        $lowerNameSpaceForTranslate = str_replace('bundle.entity', '', str_replace('\\', '.', strtolower($entityNamespace)));

        $renderedConfig = $templating->render("CorePrototypeBundle:Command:element.read.template.twig", [
            "namespace" => $entityNamespace,
            "entityName" => $entityName,
            "objectName" => strtolower($objectName),
            "fieldsInfo" => $fieldsInfo,
            "lowerNameSpaceForTranslate" => $lowerNameSpaceForTranslate,
            "isAssociated"=>$isAssociated,
            "that"=>$this
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
                $this->addFile($value['object_name'], $assocObjectFieldsInfo, $rootFolder.DIRECTORY_SEPARATOR.$objectName,true);
            }
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $entityName = $this->getEntityName($input);
        $rootFolder = $input->getArgument('rootFolder');
        $model = $this->getContainer()->get("model_factory")->getModel($entityName);
        $fieldsInfo = $model->getFieldsInfo();
        $entityReflection = new ReflectionClass($entityName);
        $objectName = $entityReflection->getShortName();
        $this->addFile($entityName, $fieldsInfo, $rootFolder);


        //generate assoc form types
        if (true === $input->getOption('withAssociated')) {

            $this->runAssociatedObjectsRecursively($fieldsInfo, $rootFolder,$objectName, $output);
        }



        $output->writeln("Show view generated");
    }

}