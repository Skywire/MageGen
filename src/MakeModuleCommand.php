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
class MakeModuleCommand extends AbstractCommand
{
    /**
     * @var Environment
     */
    protected $twig;

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'make:module';

    protected function configure(): void
    {
        parent::configure();
        $this->addArgument('namespace', InputArgument::OPTIONAL);
        $this->addArgument('module', InputArgument::OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $writer = $this->getWriter($input);

        $io = new SymfonyStyle($input, $output);

        $namespace = $input->getArgument('namespace');
        if (!$namespace) {
            $namespace = $io->askQuestion(
                new Question('Namespace')
            );
        }
        $module = $input->getArgument('module');
        if (!$module) {
            $module = $io->askQuestion(new Question('Module'));
        }

        $data = [
            'namespace' => $namespace,
            'module'    => $module,
        ];

        $written   = [];
        $written[] = $writer->writeFile(
            $namespace,
            $module,
            'etc',
            'module.xml',
            $this->twig->render('module/module.xml.twig', $data)
        );
        $written[] = $writer->writeFile(
            $namespace,
            $module,
            '',
            'registration.php',
            $this->twig->render('module/registration.php.twig', $data)
        );

        $io->section('Files Created');
        $io->text($written);

        $io->success('Module created');

        return Command::SUCCESS;
    }
}
