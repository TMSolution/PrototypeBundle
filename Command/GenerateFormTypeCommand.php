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
class GenerateFormTypeCommand extends ContainerAwareCommand
{

    protected $types = [
        "string" => "text",
        "text" => "text",
        "blob" => "text",
        "integer" => "number",
        "smallint" => "number",
        "bigint" => "number",
        "decimal" => "number",
        "float" => "number",
        "duble" => "number",
        "boolean" => "boolean",
        "datetime" => "date",
        "datetimetz" => "date",
        "date" => "date",
        "time" => "text",
        "array" => "array",
        "simple_array" => "array",
        "json_array" => "array",
        "object" => "entity"
    ];

    protected function configure()
    {
        $this->setName('prototype:generate:formtype')
                ->setDescription('Generate formtype for entity')
                ->addArgument('entity', InputArgument::REQUIRED, 'Insert entity class name')
                ->addArgument('path', InputArgument::OPTIONAL, 'Insert form type path')
                ->addArgument('rootFolder', InputArgument::OPTIONAL, 'Insert form type path')
                ->addOption('withAssociated', null, InputOption::VALUE_NONE, 'Insert associated param');
    }

    protected function getEntityName($entity)
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $entityName = str_replace('/', '\\', $entity);
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

    protected function createDirectory($path, $classPath, $entityNamespace, $objectName, $rootFolder)
    {

        if ($path) {
            $entityNamespace = $entityNamespace . DIRECTORY_SEPARATOR . $path;
        }

        $directory = $this->replaceLast("Entity", "Config\\".$rootFolder, str_replace("\\", DIRECTORY_SEPARATOR, ($classPath . "\\" . $entityNamespace)));

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

    protected function getFormTypeNamespaceName($entityName, $path,$rootFolder)
    {
        $directory = "Config\\".$rootFolder;
        if ($path) {
            $directory = str_replace(DIRECTORY_SEPARATOR, "\\", "Config\\".$rootFolder."\\" . $path);
        }
        $entityNameArr = explode("\\", str_replace("Entity", $directory, $entityName));
        unset($entityNameArr[count($entityNameArr) - 1]);
        return implode("\\", $entityNameArr);
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

    protected function addFile($entityName, $path, $fieldsInfo, $rootFolder, $output)
    {
        $classPath = $this->getClassPath($entityName);
        $entityReflection = new ReflectionClass($entityName);
        $entityNamespace = $entityReflection->getNamespaceName();
        $objectName = $entityReflection->getShortName();
        
        $lowerNameSpaceForTranslate = str_replace('bundle.entity', '', str_replace('\\', '.', strtolower($entityNamespace)));
               
        $directory = $this->createDirectory($path, $classPath, $entityNamespace, $objectName, $rootFolder);
        $fileName = $directory . DIRECTORY_SEPARATOR . "FormType.php";
        $this->isFileNameBusy($fileName);
        $templating = $this->getContainer()->get('templating');
        $formTypeNamespaceName = $this->getFormTypeNamespaceName($entityName, $path, $rootFolder);
        $formTypeName = strtolower(str_replace('\\', '_', $entityNamespace).'_',$objectName);

        foreach ($fieldsInfo as $key => $field) {
            $fieldsInfo[$key]['formType'] = $this->types[$field['type']];
        }

        $renderedConfig = $templating->render("CorePrototypeBundle:Command:formtype.template.twig", [
            "namespace" => $entityNamespace,
            "entityName" => $entityName,
            "objectName" => strtolower($objectName),
            "fieldsInfo" => $fieldsInfo,
            "formTypeNamespace" => $formTypeNamespaceName,
            "formTypeName" => $formTypeName,
            "lowerNameSpaceForTranslate"=>$lowerNameSpaceForTranslate,
            "that"=>$this
        ]);

        file_put_contents($fileName, $renderedConfig);
        $output->writeln("Form type " . $entityName . " generated");
    }

    protected function runAssociatedObjectsRecursively($fieldsInfo, $rootPath, $rootFolder, $output)
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
                $this->addFile($value['object_name'], $rootPath.DIRECTORY_SEPARATOR.$path, $assocObjectFieldsInfo, $rootFolder, $output);
            }
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //add form type
        $path = $input->getArgument('path');
        $entity = $input->getArgument('entity');
        $rootFolder = $input->getArgument('rootFolder');
        
        $entityName = $this->getEntityName($entity);
        $model = $this->getContainer()->get("model_factory")->getModel($entityName);
        $fieldsInfo = $model->getFieldsInfo();
        $this->addFile($entityName, $path, $fieldsInfo, $rootFolder, $output);


        //generate assoc form types
        if (true === $input->getOption('withAssociated')) {

            $this->runAssociatedObjectsRecursively($fieldsInfo,$path, $rootFolder,$output);
        }
    }

}
