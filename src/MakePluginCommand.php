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

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\Parameter;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Printer;
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
class MakePluginCommand extends AbstractCommand
{
    /**
     * @var Environment
     */
    protected $twig;

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'make:plugin';

    protected function configure(): void
    {
        parent::configure();
        $this->addArgument('subject', InputArgument::OPTIONAL, 'Plugin subject / target');
        $this->addArgument('method', InputArgument::OPTIONAL, 'Subject method');
        $this->addArgument('class', InputArgument::OPTIONAL, 'Plugin class name');
        $this->addArgument('type', InputArgument::OPTIONAL, "before, around, after");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        require $input->getArgument('magepath') . '/vendor/autoload.php';

        $writer = $this->getWriter($input);

        $io = new SymfonyStyle($input, $output);

        $subject = $input->getArgument('subject');
        if (!$subject) {
            $subject = $io->askQuestion(
                new Question('Subject')
            );
        }
        $method = $input->getArgument('method');
        if (!$method) {
            $method = $io->askQuestion(
                new Question('method')
            );
        }
        $classFqn = $input->getArgument('class');
        if (!$classFqn) {
            $classFqn = $io->askQuestion(new Question('Class'));
        }

        $type = $input->getArgument('type');
        if (!$type) {
            $type = $io->choice('Type', ['before', 'around', 'after']);
        }

        $file = new PhpFile();
        $file->setStrictTypes();
        $newNamespace = new PhpNamespace($this->nameHelper->getNamespace($classFqn));

        try {
            $newClass     = ClassType::withBodiesFrom($classFqn);
            $newNamespace = new PhpNamespace($this->nameHelper->getNamespace($classFqn));
            $newNamespace->add($newClass);
        } catch (\Throwable $e) {
            $newClass = new ClassType($this->nameHelper->getClass($classFqn));
        }

        $newNamespace->add($newClass);
        $file->addNamespace($newNamespace);

        $subjectClass  = ClassType::from($subject);
        $subjectMethod = $subjectClass->getMethod($method);
        switch ($type) {
            case 'before':
                $this->methodHelper->createBeforeMethod($newClass, $subject, $subjectMethod);
                break;
            case 'around':
                $this->methodHelper->createAroundMethod($newClass, $subject, $subjectMethod);
                break;
            case 'after':
                $this->methodHelper->createAfterMethod($newClass, $subject, $subjectMethod);
                break;
        }

        $writer->writeFile(
            $this->nameHelper->getVendor($classFqn),
            $this->nameHelper->getModule($classFqn),
            $this->nameHelper->getPath($classFqn),
            $newClass->getName() . '.php',
            (new Printer())->printFile($file)
        );

        return Command::SUCCESS;
    }
}
