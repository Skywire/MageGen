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
use MageGen\Writer\ClassFile;
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
class MakePluginCommand extends AbstractCommand
{
    /**
     * @var Environment
     */
    protected $twig;

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'make:plugin';

    /**
     * @var DiGenerator
     */
    protected $diGenerator;

    public function __construct(Environment $twig, string $name = null)
    {
        parent::__construct($twig, $name);

        $this->diGenerator = new DiGenerator($twig);
    }


    protected function configure(): void
    {
        parent::configure();
        $this->addArgument('subject', InputArgument::OPTIONAL, 'Plugin subject / target');
        $this->addArgument('method', InputArgument::OPTIONAL, 'Subject method');
        $this->addArgument('class', InputArgument::OPTIONAL, 'Plugin class name');
        $this->addArgument('type', InputArgument::OPTIONAL, "before, around, after");
        $this->addArgument(
            'area',
            InputArgument::OPTIONAL,
            "global, frontend, adminhtml, webapi_rest, webapi_soap, crontab"
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        require $input->getArgument('magepath') . '/vendor/autoload.php';

        $writer      = $this->getWriter($input);
        $classWriter = new ClassFile($input->getArgument('magepath'));

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
        $area = $input->getArgument('area');
        if (!$area) {
            $area = $io->choice(
                'Area',
                ['global', 'frontend', 'adminhtml', 'webapi_rest', 'webapi_soap', 'crontab'],
                0
            );
        }

        try {
            $newClass = ClassType::withBodiesFrom($classFqn);
            $isAmend  = true;
        } catch (\Throwable $e) {
            $isAmend = false;
        }

        if ($isAmend) {
            $newMethod = $this->generateMethod($subject, $method, $type, $newClass);
            $classWriter->writeMethod(
                $classFqn,
                (new PsrPrinter())->printMethod($newMethod)
            );
        } else {
            $file = new PhpFile();
            $file->setStrictTypes();

            $newNamespace = new PhpNamespace($this->nameHelper->getNamespace($classFqn));
            $newClass     = new ClassType($this->nameHelper->getClass($classFqn));

            $newNamespace->add($newClass);
            $file->addNamespace($newNamespace);

            $this->generateMethod($subject, $method, $type, $newClass);

            $writer->writeFile(
                $this->nameHelper->getVendor($classFqn),
                $this->nameHelper->getModule($classFqn),
                $this->nameHelper->getPath($classFqn),
                $newClass->getName() . '.php',
                (new PsrPrinter())->printFile($file)
            );
        }

        $diFilePath = $this->diGenerator->createDiFile(
            $this->nameHelper->getVendor($classFqn),
            $this->nameHelper->getModule($classFqn),
            $area,
            $writer
        );

        $this->diGenerator->addPlugin($diFilePath, $subject, $classFqn, $type);

        return 0;
    }

    /**
     * @param           $subject
     * @param           $method
     * @param           $type
     * @param ClassType $newClass
     *
     * @return Method
     */
    protected function generateMethod($subject, $method, $type, ClassType $newClass): Method
    {
        $subjectClass  = ClassType::from($subject);
        $subjectMethod = $subjectClass->getMethod($method);
        switch ($type) {
            case 'before':
                $newMethod = $this->methodGenerator->createBeforeMethod($newClass, $subject, $subjectMethod);
                break;
            case 'around':
                $newMethod = $this->methodGenerator->createAroundMethod($newClass, $subject, $subjectMethod);
                break;
            case 'after':
                $newMethod = $this->methodGenerator->createAfterMethod($newClass, $subject, $subjectMethod);
                break;
        }

        return $newMethod;
    }
}
