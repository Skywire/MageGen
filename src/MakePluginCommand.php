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

        $this->diGenerator = new DiGenerator();
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
        $area = $input->getArgument('area');
        if (!$area) {
            $area = $io->choice(
                'Area',
                ['global', 'frontend', 'adminhtml', 'webapi_rest', 'webapi_soap', 'crontab'],
                0
            );
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
                $this->methodGenerator->createBeforeMethod($newClass, $subject, $subjectMethod);
                break;
            case 'around':
                $this->methodGenerator->createAroundMethod($newClass, $subject, $subjectMethod);
                break;
            case 'after':
                $this->methodGenerator->createAfterMethod($newClass, $subject, $subjectMethod);
                break;
        }

        $writer->writeFile(
            $this->nameHelper->getVendor($classFqn),
            $this->nameHelper->getModule($classFqn),
            $this->nameHelper->getPath($classFqn),
            $newClass->getName() . '.php',
            (new PsrPrinter())->printFile($file)
        );

        $diFilePath = $this->createDiFile(
            $this->nameHelper->getVendor($classFqn),
            $this->nameHelper->getModule($classFqn),
            $area,
            $writer
        );

        $this->diGenerator->addPlugin($diFilePath, $subject, $classFqn, $type);

        return Command::SUCCESS;
    }

    /**
     * Create a file if it doesn't exist and return the path
     *
     * @param string     $vendor
     * @param string     $module
     * @param ModuleFile $writer
     *
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    protected function createDiFile(string $vendor, string $module, string $area, ModuleFile $writer): string
    {
        $etcPath = 'etc';
        if ($area !== 'global') {
            $etcPath .= '/' . $area;
        }

        return $writer->writeFile(
            $vendor,
            $module,
            $etcPath,
            'di.xml',
            $this->twig->render('module/di.xml.twig')
        );
    }
}
