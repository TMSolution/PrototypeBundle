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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use ReflectionClass;
use LogicException;
use UnexpectedValueException;

/**
 * GridConfigCommand generates widget class and his template.
 * @author Mariusz Piela <mariuszpiela@gmail.com>
 */
class GenerateFilesCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this->setName('prototype:generate:files')
                ->setDescription('Generate formtype for entity')
                ->addArgument('configBundle', InputArgument::REQUIRED, 'Insert configBundle')
                ->addArgument('entityOrBundle', InputArgument::REQUIRED, 'Insert Entity or Bundle')
                ->addArgument('rootFolder', InputArgument::REQUIRED, 'Insert rootFolder(SuperAdmin,Admin,Agent,...)')
                ->addOption('withGridServices', null, InputOption::VALUE_NONE, 'Generate grid services file')
                ->addOption('withFormTypeServices', null, InputOption::VALUE_NONE, 'Generate formtype services file')
                ->addOption('withFormTypes', null, InputOption::VALUE_NONE, 'Generate Formtype files')
                ->addOption('withGridConfig', null, InputOption::VALUE_NONE, 'Generate Gridconfig files')
                ->addOption('withTranslation', null, InputOption::VALUE_NONE, 'Generate Translation files')
                ->addOption('withReadTwig', null, InputOption::VALUE_NONE, 'Generate read twig element')
                ->addOption('withUpdateTwig', null, InputOption::VALUE_NONE, 'Generate update twig element')
                ->addOption('withAll', null, InputOption::VALUE_NONE, 'Generate update twig element');
    }

    protected function getConfigFilePath($manager, $input)
    {
        $bundle = $this->getApplication()->getKernel()->getBundle($input->getArgument('configBundle'));
        $bundleMetadata = $manager->getBundleMetadata($bundle);
        return str_replace('/', DIRECTORY_SEPARATOR, $bundleMetadata->getPath()) . DIRECTORY_SEPARATOR . $bundleMetadata->getNamespace() . DIRECTORY_SEPARATOR;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manager = new DisconnectedMetadataFactory($this->getContainer()->get('doctrine'));
        $configFilePath = $this->getConfigFilePath($manager, $input);


        $entities = [];
        try {
            $bundle = $this->getApplication()->getKernel()->getBundle($input->getArgument('entityOrBundle'));
            $bundleName = $bundle->getName();
            $bundleMetadata = $manager->getBundleMetadata($bundle);
            foreach ($bundleMetadata->getMetadata() as $metadata) {
                $entities[] = $metadata->getName();
            }
        } catch (\InvalidArgumentException $e) {
            try {
                $model = $this->getContainer()->get("model_factory")->getModel($input->getArgument('entityOrBundle'));
                $metadata = $model->getMetadata();
                $bundleName = str_replace('\\', '', str_replace('\Entity', null, $metadata->namespace));

                $entities[] = $metadata->getName();
            } catch (\Exception $e) {
                $output->writeln("<error>Element \"" . $input->getArgument('entityOrBundle') . "\" not exist.</error>");
                exit;
            }
        }

        $withGridServices = true === $input->getOption('withGridServices');
        $withFormTypeServices = true === $input->getOption('withFormTypeServices');
        $withFormTypes = true === $input->getOption('withFormTypes');
        $withGridConfig = true === $input->getOption('withGridConfig');
        $withTranslation = true === $input->getOption('withTranslation');
        $withReadTwig = true === $input->getOption('withReadTwig');
        $withUpdateTwig = true === $input->getOption('withUpdateTwig');

        $withAll = true === $input->getOption('withAll');

        foreach ($entities as $entity) {

            $output->writeln(sprintf('Entity: "<info>%s</info>"', $entity));

            if ($withGridServices || $withAll) {
                $output->writeln('Generate grid services.');
                $command = $this->getApplication()->find('prototype:generate:services');
                $arguments = array(
                    'configBundle' => $input->getArgument('configBundle'),
                    'rootSpace' => $input->getArgument('rootFolder'),
                    'entity' => $entity,
                    'tag' => 'prototype.gridconfig',
                    'route' => 'core_prototype_',
                    'parentEntity' => '',
                    '--withAssociated' => null,
                );
                $inputCommand = new ArrayInput($arguments);
                $returnCode = $command->run($inputCommand, $output);
            }

            if ($withFormTypeServices || $withAll) {
                $output->writeln('Generate formtype services.');
                $command = $this->getApplication()->find('prototype:generate:services');
                $arguments = array(
                    'configBundle' => $input->getArgument('configBundle'),
                    'rootSpace' => $input->getArgument('rootFolder'),
                    'entity' => $entity,
                    'tag' => 'prototype.formtype',
                    'route' => 'core_prototype_',
                    'parentEntity' => '',
                    '--withAssociated' => null
                );
                $inputCommand = new ArrayInput($arguments);
                $returnCode = $command->run($inputCommand, $output);
            }


            if ($withFormTypes || $withAll) {
                $output->writeln('Generate form types.');
                $command = $this->getApplication()->find('prototype:generate:formtype');
                $pathArr = explode('\\', $entity);
                $path = array_pop($pathArr);
                $arguments = array(
                    'entity' => $entity,
                    'rootFolder' => $input->getArgument('rootFolder'),
                    'path' => $path,
                    '--withAssociated' => null,
                );
                $inputCommand = new ArrayInput($arguments);
                $returnCode = $command->run($inputCommand, $output);
            }

            if ($withGridConfig || $withAll) {
                $output->writeln(sprintf('Generate Gridconfig file for <info>%s</info>', $entity));
                $command = $this->getApplication()->find('datagrid:generate:grid:config');
                $pathArr = explode('\\', $entity);
                $path = array_pop($pathArr);
                $arguments = array(
                    'entity' => $entity,
                    'rootFolder' => $input->getArgument('rootFolder'),
                    'path' => $path,
                    '--associated' => null
                );
                $inputCommand = new ArrayInput($arguments);
                $returnCode = $command->run($inputCommand, $output);
            }


            if ($withReadTwig || $withAll) {
                $output->writeln(sprintf('Generate translation file for <info>%s</info>', $entity));
                $command = $this->getApplication()->find('prototype:generate:twig:element:read');
                $arguments = array(
                    'entity' => $entity,
                    'rootFolder' => $input->getArgument('rootFolder')
                );
                $inputCommand = new ArrayInput($arguments);
                $returnCode = $command->run($inputCommand, $output);
            }

            if ($withUpdateTwig || $withAll) {
                $output->writeln(sprintf('Generate translation file for <info>%s</info>', $entity));
                $command = $this->getApplication()->find('prototype:generate:twig:element:update');
                $arguments = array(
                    'entity' => $entity,
                    'rootFolder' => $input->getArgument('rootFolder')
                );
                $inputCommand = new ArrayInput($arguments);
                $returnCode = $command->run($inputCommand, $output);
            }
        }

        //translation for bundle
        if (!empty($entities) && ($withTranslation || $withAll)) {

            $output->writeln(sprintf('Generate translation file for <info>%s</info>', $entity));
            $command = $this->getApplication()->find('prototype:generate:translation');

            //for polish
            $arguments = array(
                'entityOrBundle' => $bundleName,
                'shortLanguage' => 'pl'
            );
            $inputCommand = new ArrayInput($arguments);
            $returnCode = $command->run($inputCommand, $output);

            //for english
            $arguments = array(
                'entityOrBundle' => $bundleName,
                'shortLanguage' => 'en'
            );
            $inputCommand = new ArrayInput($arguments);
            $returnCode = $command->run($inputCommand, $output);
        }
    }

}
