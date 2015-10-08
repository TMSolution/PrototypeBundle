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
 * GenerateTranslationCommand generates widget class and his template.
 * @author Mariusz Piela <mariuszpiela@gmail.com>
 */
class GenerateTranslationCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        /*
         * type "OPTIONAL" for the parameter "short Language" was chosen because of compatibility with the global command prototype: generate: files. 
         * Please do not change!
         */
        $this->setName('prototype:generate:translation')
                ->setDescription('Generate translation file for bundle or entity')
                ->addArgument('entityOrBundle', InputArgument::REQUIRED, 'Insert bundle name or entity path')
                ->addArgument('shortLanguage', InputArgument::OPTIONAL, 'Insert language shortcut (pl,en,etc...)','en');
//                ->addArgument('aaa', InputArgument::OPTIONAL, 'Insert optional');
    }

    protected function getClassPath($entityName, $manager)
    {

        $classPath = $manager->getClassMetadata($entityName)->getPath();
        return $classPath;
    }

    protected function createTranslationDirectory($classPath, $entityNamespace)
    {

        $directory = str_replace("/", DIRECTORY_SEPARATOR, str_replace("\\", DIRECTORY_SEPARATOR, ($classPath . '/' . $entityNamespace)));
        $directory = $this->replaceLast("Entity", "Resources" . DIRECTORY_SEPARATOR . "translations", $directory);

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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        
      
        $language = $input->getArgument('shortLanguage');

        $manager = new DisconnectedMetadataFactory($this->getContainer()->get('doctrine'));
        $entities = [];

        try {
            $bundle = $this->getApplication()->getKernel()->getBundle($input->getArgument('entityOrBundle'));
            $output->writeln(sprintf('Generating translation for bundle "<info>%s</info>"', $bundle->getName())); 
            $bundleMetadata = $manager->getBundleMetadata($bundle);
            foreach ($bundleMetadata->getMetadata() as $metadata) {
                $entities[] = $metadata->getName();
            }
        } catch (\InvalidArgumentException $e) {
            try {
                $model = $this->getContainer()->get("model_factory")->getModel($input->getArgument('entityOrBundle'));
                $metadata = $model->getMetadata();
                $entities[] = $metadata->getName();
            } catch (\Exception $e) {
                $output->writeln("<error>Argument \"".$input->getArgument('entityOrBundle')."\" not exist.</error>");
                exit;
            }
        }

        $twigEntities = [];
        foreach ($entities as $entityName) {


            $classPath = $this->getClassPath($entityName, $manager);
            $entityReflection = new ReflectionClass($entityName);
            $methods = $entityReflection->getMethods();
            $fields = [];
            foreach ($methods as $method) {

                $name = $method->getName();

                if ($method->isPublic() && (substr($name, 0, 3) == 'get' || substr($name, 0, 3) == 'has')) {
                    $name = lcfirst(substr($name, 3));
                    $fields[] = $name;
                }
            }


            $lowerPrefix = str_replace('bundle.entity', '', str_replace('\\', '.', strtolower($entityReflection->getNamespaceName())));
            $objectName = $entityReflection->getShortName();
            $twigEntities[$entityName]["prefix"] = $lowerPrefix;
            $twigEntities[$entityName]["objectName"] = lcfirst($objectName);
            $twigEntities[$entityName]["section"] = $entityName;
            $twigEntities[$entityName]["fields"] = $fields;
        }

        $directory = $this->createTranslationDirectory($classPath, $entityReflection->getNamespaceName());
        $fileName = $directory . DIRECTORY_SEPARATOR . "messages." . $language . ".xlf";
        $this->isFileNameBusy($fileName);
        $templating = $this->getContainer()->get('templating');


        $renderedConfig = $templating->render("CorePrototypeBundle:Command:translation.template.twig", [
            "language" => $language,
            "entities" => $twigEntities
        ]);

        file_put_contents($fileName, $renderedConfig);
        $output->writeln("Translation file <info>" . $fileName . "</info> generated.");
    }

}
