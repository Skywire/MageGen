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

use MageGen\Autocomplete\EntityAutocomplete;
use MageGen\Autocomplete\ModuleAutocomplete;
use MageGen\Generator\DiGenerator;
use MageGen\Generator\EntityGenerator;
use MageGen\Generator\SchemaGenerator;
use MageGen\Helper\ModuleHelper;
use MageGen\Printer\PropertyPrinter;
use MageGen\Writer\AbstractWriter;
use MageGen\Writer\ClassFile;
use MageGen\Writer\ModuleFile;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\Parameter;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Property;
use Nette\PhpGenerator\PsrPrinter;
use Nette\PhpGenerator\Traits\NameAware;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twig\Environment;


/**
 * @method string getCommandDescription()
 */
class MakeSchemaCommand extends AbstractCommand
{
    /**
     * @var Environment
     */
    protected $twig;

    protected static $defaultName = 'make:schema';

    /**
     * @var SchemaGenerator
     */
    protected $schemaGenerator;

    public function __construct(Environment $twig, string $name = null)
    {
        parent::__construct($twig, $name);

        $this->schemaGenerator = new SchemaGenerator($twig);
    }

    protected function configure(): void
    {
        parent::configure();
        $this->addArgument('module', InputArgument::OPTIONAL, 'Plugin subject / target');
        $this->addArgument('entity', InputArgument::OPTIONAL, 'Entity name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        require $input->getArgument('magepath') . '/vendor/autoload.php';

        $io = new SymfonyStyle($input, $output);

        $module = $input->getArgument('module');
        if (!$module) {
            $module = $io->askQuestion(
                (new Question('Module'))->setAutocompleterValues(
                    (new ModuleAutocomplete())->getAutocompleteValues(
                        $input->getArgument('magepath')
                    )
                )
            );
        }

        $entity = $input->getArgument('entity');
        if (!$entity) {
            $prefix = sprintf('%s\\Model\\', str_replace('_', '\\', $module));
            $entity = $io->askQuestion(
                (new Question(sprintf('Entity %s', $prefix)))->setAutocompleterValues(
                    (new EntityAutocomplete())->getAutocompleteValues(
                        $input->getArgument('magepath'),
                        $module
                    )
                )
            );
        }

        $writer = $this->getWriter($input);

        $entityFqn = implode(
            '\\',
            [
                str_replace('_', '\\', $module),
                'Model',
                $entity,
            ]
        );

        $vendor = $this->nameHelper->getVendor($entityFqn);
        $module = $this->nameHelper->getModule($entityFqn);

        $schemaFilePath = $this->schemaGenerator->createSchemaFile(
            $vendor,
            $module,
            $writer
        );

        $this->schemaGenerator->addEntity($schemaFilePath, $entityFqn);

        return 0;
    }
}
