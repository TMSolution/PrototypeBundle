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
class GenerateTwigCommand extends ContainerAwareCommand {

    const Container = "Container";
    const Element = "Element";

    protected $viewType = null;
    protected $templatePath = null;
    protected $fileName = null;

    protected function configure() {
        /*
         * type "OPTIONAL" for the parameter "rootFolder" was chosen because of compatibility with the global command prototype:generate:files. 
         * Please do not change!
         */
        $this->setName('prototype:generate:twig')
                ->setDescription('Generate twig element view template.')
                ->addOption('entity', 'ent', InputOption::VALUE_REQUIRED, 'Full Entity Name')
                ->addOption('viewType', 'vt', InputOption::VALUE_REQUIRED, 'Container or Element')
                ->addOption('templatePath', 'tp', InputOption::VALUE_REQUIRED, 'Twig template Path')
                ->addOption('fileName', 'fn', InputOption::VALUE_REQUIRED, 'Insert file name for new file')
                ->addOption('rootFolder', 'rf', InputOption::VALUE_OPTIONAL, 'Insert rootFolder')
                ->addOption('withAssociated', 'wa', InputOption::VALUE_OPTIONAL, 'Insert associated param');
    }

    protected function getEntityName($input) {
        $doctrine = $this->getContainer()->get('doctrine');
        $entityName = str_replace('/', '\\', $input->getOption('entity'));
        if (($position = strpos($entityName, ':')) !== false) {
            $entityName = $doctrine->getAliasNamespace(substr($entityName, 0, $position)) . '\\' . substr($entityName, $position + 1);
        }

        return $entityName;
    }

    protected function getClassPath($entityName) {
        $manager = new DisconnectedMetadataFactory($this->getContainer()->get('doctrine'));
        $classPath = $manager->getClassMetadata($entityName)->getPath();
        return $classPath;
    }

    protected function getGridConfigNamespaceName($entityName) {

        $entityNameArr = explode("\\", str_replace("Entity", "Grid", $entityName));
        unset($entityNameArr[count($entityNameArr) - 1]);
        return implode("\\", $entityNameArr);
    }

    /* ? */

    protected function createDirectory($classPath, $entityNamespace, $objectName, $rootFolder, $configEntityName, $viewType) {

        $confgEntityReflection = new ReflectionClass($configEntityName);
        $configEntityNamespace = $confgEntityReflection->getNamespaceName();
        $entityNamespace = $configEntityNamespace;

        $directory = str_replace("\\", DIRECTORY_SEPARATOR, ($classPath . "\\" . $entityNamespace));
        $directory = $this->replaceLast("Entity", "Resources" . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR . $rootFolder . DIRECTORY_SEPARATOR . $objectName . DIRECTORY_SEPARATOR . $viewType, $directory);

        if (is_dir($directory) == false) {
            if (mkdir($directory, 0777, true) == false) {
                throw new UnexpectedValueException("Creating directory failed: " . $directory);
            }
        }


        return $directory;
    }

    protected function calculateFileName($entityReflection) {

        $fileName = $this->replaceLast("Entity", "Grid", $entityReflection->getFileName());
        return $fileName;
    }

    protected function isFileNameBusy($fileName) {
        if (file_exists($fileName) == true) {
            throw new LogicException("File " . $fileName . " exists!");
        }
        return false;
    }

    protected function replaceLast($search, $replace, $subject) {
        $position = strrpos($subject, $search);
        if ($position !== false) {
            $subject = \substr_replace($subject, $replace, $position, strlen($search));
        }
        return $subject;
    }

    protected function getAssociatedObjects($fieldsInfo) {

        $associations = [];
        foreach ($fieldsInfo as $key => $value) {

            $associationTypes = ["OneToMany", "ManyToMany", "OneToOne"];
            $field = $fieldsInfo[$key];
            if (array_key_exists("association", $field) && in_array($field["association"], $associationTypes)) {
                $associations[$key] = $field;
                $associations[$key]["object_name"] = str_replace('\\', '\\\\', $field["object_name"]);
                $associations[$key]["object_name_stripslashes"] = $field["object_name"];
            }
        }

        return $associations;
    }

//    protected function getConfigEntityName($input, $output) {
//        $manager = new DisconnectedMetadataFactory($this->getContainer()->get('doctrine'));
//
//
//        try {
//
//            $configBundle = $this->getApplication()->getKernel()->getBundle($input->getOption('bundleName'));
//            $configBundleMetadata = $manager->getBundleMetadata($configBundle);
//            $configMetadata = $configBundleMetadata->getMetadata();
//            $configEntityName = $configMetadata[0]->getName();
//        } catch (\InvalidArgumentException $e) {
//            try {
//                $configModel = $this->getContainer()->get("model_factory")->getModel($input->getOption('bundleName'));
//                $configMetadata = $configModel->getMetadata();
//                $configEntityName = $configMetadata->getName();
//            } catch (\Exception $e) {
//                $output->writeln("<error>Argument configBundle:\"" . $input->getOption('bundleName') . "\" not exist.</error>");
//                exit;
//            }
//        }
//
//
//        if (!$configEntityName) {
//            $output->writeln("<error>Argument configEntityName not exist.</error>");
//            exit;
//        }
//
//        return $configEntityName;
//    }

    //?
    protected function execute(InputInterface $input, OutputInterface $output) {

        $this->viewType = $input->getOption('viewType');
        $this->templatePath = $input->getOption('templatePath');
        $this->fileName = $input->getOption('fileName');

        $entityName = $this->getEntityName($input);
        $rootFolder = $input->getOption('rootFolder');
        $model = $this->getContainer()->get("model_factory")->getModel($entityName);
        $fieldsInfo = $model->getFieldsInfo();

        /* @to do: co to i po co */
        $fieldsInfo = $this->analizeFieldName($fieldsInfo);



        $entityReflection = new ReflectionClass($entityName);
        $objectName = $entityReflection->getShortName();

        //configNameSpace
        //wydaje się, że to śmieć
        //$configEntityName = $this->getConfigEntityName($input, $output);

        $directory=$this->addFile($entityName, $fieldsInfo, $rootFolder, $entityName);


        //generate assoc form types
        if (true === $input->getOption('withAssociated')) {

            $this->runAssociatedObjectsRecursively($fieldsInfo, $rootFolder, $objectName, $output, $entityName);
        }

        $output->writeln("Twig {$this->fileName} generated in {$directory}");
    }

    protected function addFile($entityName, $fieldsInfo, $rootFolder, $configEntityName) {
        $associations = $this->getAssociatedObjects($fieldsInfo);

        $classPath = $this->getClassPath($configEntityName);
        $entityReflection = new ReflectionClass($entityName);
        $entityNamespace = $entityReflection->getNamespaceName();
        $objectName = $entityReflection->getShortName();
        $directory = $this->createDirectory($classPath, $entityNamespace, $objectName, $rootFolder, $configEntityName, $this->viewType);
        $fileName = $directory . DIRECTORY_SEPARATOR . $this->fileName;
        $this->isFileNameBusy($fileName);
        $templating = $this->getContainer()->get('templating');

        $lowerNameSpaceForTranslate = str_replace('bundle.entity', '', str_replace('\\', '.', strtolower($entityNamespace)));

        $renderedConfig = $templating->render($this->templatePath, [
            "namespace" => $entityNamespace,
            "entityName" => $entityName,
            "objectName" => $objectName,
            "fieldsInfo" => $fieldsInfo,
            "associations" => $associations,
            "lowerNameSpaceForTranslate" => $lowerNameSpaceForTranslate
        ]);

        file_put_contents($fileName, $renderedConfig);
        return $directory;
    }

    protected function runAssociatedObjectsRecursively($fieldsInfo, $rootFolder, $objectName, $output, $configEntityName) {
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
                $this->addFile($value['object_name'], $assocObjectFieldsInfo, $rootFolder . DIRECTORY_SEPARATOR . $objectName, $configEntityName);
            }
        }
    }

    protected function analizeFieldName($fieldsInfo) {


        foreach ($fieldsInfo as $key => $value) {


            if (array_key_exists("association", $fieldsInfo[$key]) && ( $fieldsInfo[$key]["association"] == "ManyToOne" || $fieldsInfo[$key]["association"] == "OneToOne" )) {

                if ($fieldsInfo[$key]["association"] == "ManyToMany") {
                    $this->manyToManyRelationExists = true;
                }


                $model = $this->getContainer()->get("model_factory")->getModel($fieldsInfo[$key]["object_name"]);
                if ($model->checkPropertyByName("name")) {
                    $fieldsInfo[$key]["default_field"] = "name";
                    $fieldsInfo[$key]["default_field_type"] = "Text";
                } else {
                    $fieldsInfo[$key]["default_field"] = "id";
                    $fieldsInfo[$key]["default_field_type"] = "Number";
                }
            } elseif (array_key_exists("association", $fieldsInfo[$key]) && ( $fieldsInfo[$key]["association"] == "ManyToMany" || $fieldsInfo[$key]["association"] == "OneToMany" )) {
                unset($fieldsInfo[$key]);
            }
        }

        return $fieldsInfo;
    }

    public function getDefaultField($entityName) {
        $model = $this->getContainer()->get("model_factory")->getModel($entityName);
        if ($model->checkPropertyByName("name")) {
            return "name";
        } else {
            return "id";
        }
    }

}
