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

use MageGen\Autocomplete\ModuleAutocomplete;
use MageGen\Generator\SchemaPatchGenerator;
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
class MakeSchemaPatchCommand extends AbstractCommand
{
    /**
     * @var Environment
     */
    protected $twig;

    protected static $defaultName = 'make:schema-patch';

    /**
     * @var SchemaPatchGenerator
     */
    private $schemaPatchGenerator;


    public function __construct(Environment $twig, string $name = null)
    {
        parent::__construct($twig, $name);

        $this->schemaPatchGenerator = new SchemaPatchGenerator($twig);
    }

    protected function configure(): void
    {
        parent::configure();
        $this->addOption('magepath', 'm', InputOption::VALUE_REQUIRED, 'Path to Magento installation', getcwd());
        $this->addArgument('module', InputArgument::OPTIONAL, 'Module name');
        $this->addArgument('patch_name', InputArgument::OPTIONAL, 'Patch Name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        require $input->getOption('magepath') . '/vendor/autoload.php';

        $io = new SymfonyStyle($input, $output);

        $module = $input->getArgument('module');
        if (!$module) {
            $module = $io->askQuestion(
                (new Question('Module'))->setAutocompleterValues(
                    (new ModuleAutocomplete())->getAutocompleteValues(
                        $input->getOption('magepath')
                    )
                )
            );
        }

        $patchName = $input->getArgument('patch_name');
        if (!$patchName) {
            $patchName = $io->askQuestion(
                new Question('Patch Name')
            );
        }

        [$vendor, $module] = explode('_', $module);

        $patchFilePath = $this->schemaPatchGenerator->createSchemaPatch(
            $vendor,
            $module,
            $patchName,
            $this->getWriter($input)
        );

        return 0;
    }
}
