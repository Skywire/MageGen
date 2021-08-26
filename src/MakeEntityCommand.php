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

    public function __construct(Environment $twig, string $name = null)
    {
        parent::__construct($twig, $name);

        $this->diGenerator = new DiGenerator();
    }

    protected function configure(): void
    {
        parent::configure();
        $this->addArgument('module', InputArgument::OPTIONAL, 'Plugin subject / target');
        $this->addArgument('entity', InputArgument::OPTIONAL, 'Entity name');
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

        $generator = new EntityGenerator();
        $generator->makeEntity($module, $entity);

        return self::SUCCESS;
    }
}
