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
use Core\PrototypeBundle\Component\Yaml\Parser;
use Core\PrototypeBundle\Component\Yaml\Dumper;
use ReflectionClass;
use LogicException;
use UnexpectedValueException;

/**
 * GenerateTranslationCommand generates widget class and his template.
 * @author Mariusz Piela <mariuszpiela@gmail.com>
 * @author Jacek Łoziński <jacek.lozinski@tmsolution.pl>
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
                ->addArgument('shortLanguage', InputArgument::OPTIONAL, 'Insert language shortcut (pl,en,etc...)', 'en');
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

    protected function readYml($configFullPath)
    {
        try {
            if (file_exists($configFullPath)) {
                $yaml = new Parser();
                $yamlArr = $yaml->parse(file_get_contents($configFullPath));
                if ($yamlArr === NULL) {
                    $yamlArr = [];
                }
            } else {
                $yamlArr = [];
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
        file_put_contents($fileName, str_replace("'@service_container'", "@service_container", $yamlData));
        $output->writeln("Services configuration file <info>" . $fileName . "</info> generated.");
    }

    protected function checkKeyExist($yamlArr, $objectName, $key, $nameSpace)
    {

        if (array_key_exists($nameSpace, $yamlArr)) {
            if (array_key_exists($objectName, $yamlArr[$nameSpace])) {
                return array_key_exists($key, $yamlArr[$nameSpace][$objectName]);
            }
            return false;
        }
        return false;
    }

    protected function createYamlPrefix($prefix)
    {
        $yaml = NULL;
        $prefixArr = explode('.', $prefix);
        $i = 0;

        foreach ($prefixArr as $prefixEl) {



            for ($z = 1; $z <= $i; $z++) {
                $yaml.="\x20\x20\x20\x20";
            }
            $yaml.=$prefixEl . ": \n";
            $i++;
        }

        return $yaml;
    }

    function get_last_child_recursive($array)
    {
        if (is_array(end($array))) {
            return $this->get_last_child_recursive(end($array));
        } else {
            return key($array);
        }
    }

    protected function getDefaultField($entityName)
    {
        $model = $this->getContainer()->get("model_factory")->getModel($entityName);
        if ($model->checkPropertyByName("name")) {
            return "name";
        } else {
            return "id";
        }
    }

    protected function checkAssociation($entityName, $fieldName)
    {

        $model = $this->getContainer()->get("model_factory")->getModel($entityName);
        $fieldsInfo = $model->getFieldsInfo();

        if (array_key_exists($fieldName, $fieldsInfo)) {

            if (array_key_exists('association', $fieldsInfo[$fieldName])) {
                return true;
            }
        }
        return false;
    }

    protected function addYamlData(&$yamlArr, $entityName, $manager, $lowerNameSpace)
    {



        $classPath = $this->getClassPath($entityName, $manager);
        $entityReflection = new ReflectionClass($entityName);
        $objectName = $entityReflection->getShortName();
        $methods = $entityReflection->getMethods();

        foreach ($methods as $method) {



            $name = $method->getName();

            if ($method->isPublic() && (substr($name, 0, 3) == 'get' || substr($name, 0, 3) == 'has')) {
                $name = strtolower(substr($name, 3));
                $objectName = strtolower($objectName);


                if (!$this->checkKeyExist($yamlArr, $objectName, $name, $lowerNameSpace)) {

                    if ($this->checkAssociation($entityName, $name)) {
                        $defaultField = $this->getDefaultField($entityName);
                        $yamlArr[$lowerNameSpace][$objectName][$name][$defaultField] = $name;
                    } else {
                        $yamlArr[$lowerNameSpace][$objectName][$name] = $name;
                    }
                }
            }
        }
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
                $output->writeln("<error>Argument \"" . $input->getArgument('entityOrBundle') . "\" not exist.</error>");
                exit;
            }
        }


        if (!empty($entities)) {




            $entityReflection = new ReflectionClass($entities[0]);
            $classPath = $this->getClassPath($entities[0], $manager);
            $directory = $this->createTranslationDirectory($classPath, $entityReflection->getNamespaceName());
            $shortObjectName = $entityReflection->getShortName();

            $configFullPath = $directory . DIRECTORY_SEPARATOR . "messages." . $language . ".yml";

            $lowerNameSpace = str_replace('bundle.entity', '', str_replace('\\', '.', strtolower($entityReflection->getNamespaceName())));

            $yamlArr = $this->readYml($configFullPath);

            $twigEntities = [];
            foreach ($entities as $entityName) {

                $this->addYamlData($yamlArr, $entityName, $manager, $lowerNameSpace);
            }

            $this->writeYml($configFullPath, $yamlArr, $output);
            $output->writeln("Translation file <info>" . $configFullPath . "</info> generated.");
        } else {
            $output->writeln("<error>No entities !!!.</error>");
        }
    }

}
