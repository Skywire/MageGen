<?php

/**
 * Copyright Skywire. All rights reserved.
 * See LICENSE.txt for license details.
 *
 * @author      Skywire Core Team
 * @copyright   Copyright (c) 2021 Skywire (http://www.skywire.co.uk)
 */
declare(strict_types=1);

namespace MageGen;

use MageGen\Generator\DiGenerator;
use MageGen\Generator\EntityGenerator;
use MageGen\Helper\ModuleHelper;
use MageGen\Writer\ModuleFile;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\Parameter;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PsrPrinter;
use Nette\PhpGenerator\Traits\NameAware;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twig\Environment;


/**
 * @method string getCommandDescription()
 */
class MakeEntityCommand extends AbstractCommand
{
    /**
     * @var Environment
     */
    protected $twig;

    protected static $defaultName = 'make:entity';

    /**
     * @var DiGenerator
     */
    protected $diGenerator;

    /**
     * @var EntityGenerator
     */
    protected $entityGenerator;

    public function __construct(Environment $twig, string $name = null)
    {
        parent::__construct($twig, $name);

        $this->diGenerator = new DiGenerator();
        $this->entityGenerator = new EntityGenerator();
    }

    protected function configure(): void
    {
        parent::configure();
        $this->addArgument('module', InputArgument::OPTIONAL, 'Plugin subject / target');
        $this->addArgument('entity', InputArgument::OPTIONAL, 'Entity name');
        $this->addArgument('table', InputArgument::OPTIONAL, 'DB table name');
        $this->addArgument('id', InputArgument::OPTIONAL, 'DB ID field name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $writer       = $this->getWriter($input);
        $moduleHelper = new ModuleHelper();
        $io           = new SymfonyStyle($input, $output);

        $module = $input->getArgument('module');
        if (!$module) {
            $module = $io->askQuestion(
                (new Question('Module'))->setAutocompleterValues(
                    $moduleHelper->getModuleList(
                        $input->getArgument('magepath')
                    )
                )
            );
        }

        $entity = $input->getArgument('entity');
        if (!$entity) {
            $entity = $io->askQuestion(new Question('Entity'));
        }

        $table = $input->getArgument('table');
        if (!$table) {
            $table = $io->askQuestion(new Question('Table'));
        }

        $idField = $input->getArgument('id');
        if (!$idField) {
            $idField = $io->askQuestion(new Question('id'));
        }

        $classFqn = implode(
            '\\',
            [
                str_replace('_', '\\', $module),
                'Model',
                $entity,
            ]
        );

        try {
            $targetClass = ClassType::withBodiesFrom($classFqn);
            $isAmend     = true;
        } catch (\Throwable $e) {
            $isAmend = false;
        }


        if ($isAmend) {
        } else {

            [$file, $interfaceFqn] = $this->entityGenerator->createInterface($classFqn);
            $writer->writeFile(
                $this->nameHelper->getVendor($interfaceFqn),
                $this->nameHelper->getModule($interfaceFqn),
                $this->nameHelper->getPath($interfaceFqn),
                $this->nameHelper->getClass($interfaceFqn) . '.php',
                (new PsrPrinter())->printFile($file)
            );

            [$file, $classFqn] = $this->entityGenerator->createEntity($module, $classFqn, $interfaceFqn);
            $writer->writeFile(
                $this->nameHelper->getVendor($classFqn),
                $this->nameHelper->getModule($classFqn),
                $this->nameHelper->getPath($classFqn),
                $this->nameHelper->getClass($classFqn) . '.php',
                (new PsrPrinter())->printFile($file)
            );

            [$file, $resourceFqn] = $this->entityGenerator->createResource($classFqn, $table, $idField);
            $writer->writeFile(
                $this->nameHelper->getVendor($resourceFqn),
                $this->nameHelper->getModule($resourceFqn),
                $this->nameHelper->getPath($resourceFqn),
                $this->nameHelper->getClass($resourceFqn) . '.php',
                (new PsrPrinter())->printFile($file)
            );

            [$file, $collectionFqn] = $this->entityGenerator->createCollection($classFqn, $resourceFqn, $idField);
            $writer->writeFile(
                $this->nameHelper->getVendor($collectionFqn),
                $this->nameHelper->getModule($collectionFqn),
                $this->nameHelper->getPath($collectionFqn),
                $this->nameHelper->getClass($collectionFqn) . '.php',
                (new PsrPrinter())->printFile($file)
            );
        }


        return self::SUCCESS;
    }
}
