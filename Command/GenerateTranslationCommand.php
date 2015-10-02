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
class GenerateTranslationCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this->setName('prototype:generate:translation')
                ->setDescription('Generate translation file for bundle')
                ->addArgument('bundleName', InputArgument::REQUIRED, 'Insert bundle name')
                ->addArgument('language', InputArgument::REQUIRED, 'Insert language shortcut (pl,en,etc...)');
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
        $language = $input->getArgument('language');

        $manager = new DisconnectedMetadataFactory($this->getContainer()->get('doctrine'));

        try {
            $bundle = $this->getApplication()->getKernel()->getBundle($input->getArgument('bundleName'));
            $output->writeln(sprintf('Generating translation for bundle "<info>%s</info>"', $bundle->getName()));
            $metadata = $manager->getBundleMetadata($bundle);
        } catch (\InvalidArgumentException $e) {
            throw new UnexpectedValueException("Bundle " . $input->getArgument('bundleName') . " not exist");
        }

        $entities = [];
        foreach ($metadata->getMetadata() as $m) {
            $entityName = $m->getName();

            $model = $this->getContainer()->get("model_factory")->getModel($entityName);
            $fieldsInfo = $model->getFieldsInfo();

            $classPath = $this->getClassPath($entityName, $manager);
            $entityReflection = new ReflectionClass($entityName);
            $lowerPrefix = str_replace('bundle.entity', '', str_replace('\\', '.', strtolower($entityReflection->getNamespaceName())));

            // $lowerObjectName = strtolower($entityReflection->getShortName());
            $entities[$entityName]["prefix"] = $lowerPrefix;
            //$entities[$entityName]["objectName"] = $lowerObjectName;
            $entities[$entityName]["fieldsInfo"] = $fieldsInfo;
        }

        $directory = $this->createTranslationDirectory($classPath, $entityReflection->getNamespaceName());
        $fileName = $directory . DIRECTORY_SEPARATOR . "messages." . $language . ".xlf";
        $this->isFileNameBusy($fileName);
        $templating = $this->getContainer()->get('templating');


        $renderedConfig = $templating->render("CorePrototypeBundle:Command:translation.template.twig", [
            "language" => $language,
            "entities" => $entities
        ]);

        file_put_contents($fileName, $renderedConfig);
        $output->writeln("Translation file <info>" . $fileName . "</info> generated.");
    }

}
