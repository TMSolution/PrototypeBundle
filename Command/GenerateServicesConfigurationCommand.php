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
use Symfony\Component\Yaml\Parser;
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

    protected function createTranslationDirectory($classPath, $entityNamespace)
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

    protected function readYml($file)
    {
        try {
            $yaml = new Parser();
            return $yaml->parse(file_get_contents($file));
        } catch (\Exception $e) {
            throw new \Exception('Error reading yml file.');
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {


        $manager = new DisconnectedMetadataFactory($this->getContainer()->get('doctrine'));
        $entities = [];

        try {
            $bundle = $this->getApplication()->getKernel()->getBundle($input->getArgument('name'));
            $output->writeln(sprintf('Generating services.yml  "<info>%s</info>"', $bundle->getName()));
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
        $fileName = $directory . DIRECTORY_SEPARATOR . "services" . ".yml";
        //$this->isFileNameBusy($fileName);


        $yamlArr = $this->readYml($fileName);
        dump($yamlArr['services']);
        exit;

        $templating = $this->getContainer()->get('templating');


        $renderedConfig = $templating->render("CorePrototypeBundle:Command:services.template.twig", [
            "entities" => $twigEntities
        ]);

        file_put_contents($fileName, $renderedConfig, FILE_APPEND);
        $output->writeln("Services configuration file <info>" . $fileName . "</info> generated.");
    }

}
