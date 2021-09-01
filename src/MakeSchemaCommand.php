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
use MageGen\Generator\SchemaGenerator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twig\Environment;

/**
 * @method string getCommandDescription()
 */
class MakeSchemaCommand extends AbstractCommand
{
    protected static $defaultName = 'make:schema';

    /**
     * @var Environment
     */
    protected $twig;

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
        $this->addOption('magepath', 'm', InputOption::VALUE_REQUIRED, 'Path to Magento installation', getcwd());
        $this->addArgument('module', InputArgument::OPTIONAL, 'Module name');
        $this->addArgument('entity', InputArgument::OPTIONAL, 'Entity name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        require $input->getOption('magepath') . '/vendor/autoload.php';

        $io = new SymfonyStyle($input, $output);

        $module = $this->getModuleAnswer($input, $io);

        $entity = $input->getArgument('entity');
        if (!$entity) {
            $prefix = sprintf('%s\\Model\\', str_replace('_', '\\', $module));
            $entity = $io->askQuestion(
                (new Question(sprintf('Entity %s', $prefix)))->setAutocompleterValues(
                    (new EntityAutocomplete($input->getOption('magepath'), $module))->getAutocompleteValues(
                        $input->getOption('magepath'),
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
