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
class GenerateContainerReadViewCommand extends ContainerAwareCommand {

    protected function configure() {
        $this->setName('generate:twig:container:read')
                ->setDescription('Generate container twig for read action.')
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
    
    protected function getGridConfigNamespaceName($entityName)
    {   
       
         $entityNameArr=explode("\\", str_replace("Entity", "Grid", $entityName));
         unset($entityNameArr[count($entityNameArr)-1]);
         return implode("\\",$entityNameArr);
        
    }
    

    protected function createDirectory($classPath,$entityNamespace,$objectName) {
        
        
       $directory = str_replace("\\", DIRECTORY_SEPARATOR, ($classPath . "\\" . $entityNamespace));
       $directory=$this->replaceLast("Entity", "Resources".DIRECTORY_SEPARATOR."views".DIRECTORY_SEPARATOR.$objectName.DIRECTORY_SEPARATOR."Container", $directory);
      
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

    protected function execute(InputInterface $input, OutputInterface $output) {

        $entityName = $this->getEntityName($input);
        $model=$this->getContainer()->get("model_factory")->getModel($entityName);
        $fieldsInfo=$model->getFieldsInfo();  
        $classPath = $this->getClassPath($entityName);
        $entityReflection = new ReflectionClass($entityName);
        $entityNamespace = $entityReflection->getNamespaceName();
        $objectName = $entityReflection->getShortName();
        $directory=$this->createDirectory($classPath,$entityNamespace,$objectName);
        $fileName=$directory.DIRECTORY_SEPARATOR."read.html.twig";
        $this->isFileNameBusy($fileName);
        $templating = $this->getContainer()->get('templating');
        
        var_dump($fieldsInfo);
        
       
        $renderedConfig = $templating->render("CorePrototypeBundle:Command:container.read.template.twig", [
            "namespace" => $entityNamespace,
            "entityName" => $entityName,
            "objectName" => $objectName,
            "fieldsInfo" => $fieldsInfo
            ]);
        
        file_put_contents($fileName, $renderedConfig);
        $output->writeln("Show view generated");
    }

   

}
