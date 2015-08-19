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
class GenerateFormTypeCommand extends ContainerAwareCommand {

    protected function configure() {
        $this->setName('generate:view:update')
                ->setDescription('Generate widget and template')
                ->addArgument(
                        'entity', InputArgument::REQUIRED, 'Insert entity class name'
        );
    }

    protected function getEntityName($input) {
        $doctrine = $this->getContainer()->get('doctrine');
        $entityName = str_replace('/', '\\', $input->getArgument('entity'));
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
    

    

    protected function createDirectory($classPath,$entityNamespace,$objectName) {
        
        
       $directory = str_replace("\\", DIRECTORY_SEPARATOR, ($classPath . "\\" . $entityNamespace));
       $directory=$this->replaceLast("Entity", "Form", $directory);
      
        if (is_dir($directory) == false) {
            if (mkdir($directory,0777,true) == false) {
                throw new UnexpectedValueException("Creating directory failed: ".$directory);
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
            throw new LogicException("File ".$fileName." exists!");
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
    
    protected function getFormTypeNamespaceName($entityName)
    {   
       
         $entityNameArr=explode("\\", str_replace("Entity", "Form\\Type", $entityName));
         unset($entityNameArr[count($entityNameArr)-1]);
         return implode("\\",$entityNameArr);
        
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        $entityName = $this->getEntityName($input);
        $model=$this->getContainer()->get("model_factory")->getModel($entityName);
        $fieldsInfo=$model->getFieldsInfo();  
        $classPath = $this->getClassPath($entityName);
        $entityReflection = new ReflectionClass($entityName);
        $entityNamespace = $entityReflection->getNamespaceName();
        $objectName = $entityReflection->getShortName();
        $directory=$this->createDirectory($classPath,$entityNamespace,$objectName);
        $fileName=$directory.DIRECTORY_SEPARATOR.$objectName."Type.php";
        $this->isFileNameBusy($fileName);
        $templating = $this->getContainer()->get('templating');
        $formTypeNamespaceName=$this->getFormTypeNamespaceName($entityName);
        $formTypeName=  strtolower(str_replace('/', '_', $entityNamespace));

        $renderedConfig = $templating->render("CorePrototypeBundle:Command:update.template.twig", [
            "namespace" => $entityNamespace,
            "entityName" => $entityName,
            "objectName" => $objectName,
            "fieldsInfo" => $fieldsInfo,
            "formTypeNamespace" => $formTypeNamespaceName,
            "formTypeName" => $formTypeName
            ]);
        
        file_put_contents($fileName, $renderedConfig);
        $output->writeln("Update view generated");
    }

   

}
