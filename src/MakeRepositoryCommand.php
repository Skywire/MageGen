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
class MakeRepositoryCommand extends AbstractCommand
{
    /**
     * @var Environment
     */
    protected $twig;

    protected static $defaultName = 'make:repository';

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

        $this->diGenerator     = new DiGenerator($twig);
        $this->entityGenerator = new EntityGenerator();
    }

    protected function configure(): void
    {
        parent::configure();
        $this->addArgument('module', InputArgument::OPTIONAL, 'Plugin subject / target');
        $this->addArgument('entity', InputArgument::OPTIONAL, 'Entity name');
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

        $entity = $input->getArgument('entity');
        if (!$entity) {
            $prefix = sprintf('%s\\Model\\', str_replace('_', '\\', $module));
            $entity = $io->askQuestion(
                (new Question(sprintf('Entity %s', $prefix)))->setAutocompleterValues(
                    (new EntityAutocomplete())->getAutocompleteValues(
                        $input->getOption('magepath'),
                        $module
                    )
                )
            );
        }

        $writer          = $this->getWriter($input);
        $entityGenerator = new EntityGenerator();

        $entityFqn = implode(
            '\\',
            [
                str_replace('_', '\\', $module),
                'Model',
                $entity,
            ]
        );

        $vendor       = $this->nameHelper->getVendor($entityFqn);
        $module       = $this->nameHelper->getModule($entityFqn);
        $interfaceFqn = $entityGenerator->entityFqnToInterfaceFqn($entityFqn);

        $data = [
            'vendor'        => $vendor,
            'module'        => $module,
            'entity'        => $entity,
            'interface'     => $this->nameHelper->getClass($interfaceFqn),
            'entity_fqn'    => $entityFqn,
            'interface_fqn' => $interfaceFqn,
        ];

        $written   = [];
        $written[] = $writer->writeFile(
            $vendor,
            $module,
            'Api/Data',
            "{$entity}SearchResultInterface.php",
            $this->twig->render('Api/Data/EntitySearchResultInterface.php.twig', $data)
        );

        $written[] = $writer->writeFile(
            $vendor,
            $module,
            'Model',
            "{$entity}SearchResult.php",
            $this->twig->render('Model/EntitySearchResult.php.twig', $data)
        );


        $written[] = $writer->writeFile(
            $vendor,
            $module,
            'Api',
            "{$entity}RepositoryInterface.php",
            $this->twig->render('Api/EntityRepositoryInterface.php.twig', $data)
        );

        $written[] = $writer->writeFile(
            $vendor,
            $module,
            'Model',
            "{$entity}Repository.php",
            $this->twig->render('Model/EntityRepository.php.twig', $data)
        );

        // Write repository and search result preferences
        $diFilePath               = $this->diGenerator->createDiFile(
            $this->nameHelper->getVendor($entityFqn),
            $this->nameHelper->getModule($entityFqn),
            'global',
            $writer
        );
        $repositoryInterfaceFqn   = implode(
            '\\',
            [
                $vendor,
                $module,
                'Api',
                "{$entity}RepositoryInterface",
            ]
        );
        $repositoryFqn            = implode(
            '\\',
            [
                $vendor,
                $module,
                'Model',
                "{$entity}Repository",
            ]
        );
        $searchResultInterfaceFqn = implode(
            '\\',
            [
                $vendor,
                $module,
                'Api\Data',
                "{$entity}SearchResultInterface",
            ]
        );
        $searchResultFqn          = implode(
            '\\',
            [
                $vendor,
                $module,
                'Model',
                "{$entity}SearchResult",
            ]
        );
        $this->diGenerator->addRepositoryInterfaces(
            $diFilePath,
            $repositoryInterfaceFqn,
            $repositoryFqn,
            $searchResultInterfaceFqn,
            $searchResultFqn
        );

        $io->section('Files Created');
        $io->text($written);

        return 0;
    }
}
