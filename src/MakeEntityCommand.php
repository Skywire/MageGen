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
use MageGen\Writer\ClassFile;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PsrPrinter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
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

        $this->diGenerator     = new DiGenerator($twig);
        $this->entityGenerator = new EntityGenerator();
    }

    protected function configure(): void
    {
        parent::configure();
        $this->addOption('magepath', 'm', InputOption::VALUE_REQUIRED, 'Path to Magento installation', getcwd());
        $this->addArgument('module', InputArgument::OPTIONAL, 'Module name');
        $this->addArgument('entity', InputArgument::OPTIONAL, 'Entity name');
        $this->addArgument('table', InputArgument::OPTIONAL, 'DB table name');
        $this->addArgument('id', InputArgument::OPTIONAL, 'DB ID field name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        require $input->getOption('magepath') . '/vendor/autoload.php';

        $writer = $this->getWriter($input);
        $io     = new SymfonyStyle($input, $output);

        $module = $input->getArgument('module');
        if (!$module) {
            $module = $io->askQuestion(
                (new Question('Module'))->setAutocompleterValues(
                    (new ModuleAutocomplete($input->getOption('magepath')))->getAutocompleteValues(
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
                    (new EntityAutocomplete($input->getOption('magepath'), $module))->getAutocompleteValues(
                        $input->getOption('magepath'),
                        $module
                    )
                )
            );
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
            ClassType::withBodiesFrom($classFqn);
            $isAmend = true;
        } catch (\Throwable $e) {
            $isAmend = false;
        }

        if ($isAmend) {
            $classWriter = new ClassFile($input->getOption('magepath'));

            $io->title('Class exists, adding new properties');

            while (true) {
                $propertyName = $io->askQuestion(new Question('Property'));
                if (!$propertyName) {
                    break;
                }
                $propertyType     = $io->askQuestion(new Question('Type', 'string'));
                $propertyNullable = $io->askQuestion((new ChoiceQuestion('Nullable', ['y', 'n'], 'n'))) === 'y';

                $getter = new Method("get" . ucfirst($propertyName));
                $getter->setPublic();

                $setter = new Method("set" . ucfirst($propertyName));
                $setter->setPublic();
                $setter->addParameter($propertyName)->setType($propertyType)->setNullable($propertyNullable);

                // Add getter / setter to interface
                try {
                    $interfaceFqn = $this->entityGenerator->entityFqnToInterfaceFqn($classFqn);
                    ClassType::withBodiesFrom($interfaceFqn);

                    $setter->setBody(null);
                    $setter->setReturnType('\\' . $interfaceFqn);
                    $classWriter->writeMethod($interfaceFqn, (new PsrPrinter())->printMethod($setter));
                    $getter->setBody(null);
                    $getter->setReturnType($propertyType)->setReturnNullable($propertyNullable);
                    $classWriter->writeMethod($interfaceFqn, (new PsrPrinter())->printMethod($getter));
                } catch (\Throwable $e) {
                    // interface is optional
                    throw $e;
                }

                // Add getter / setter to class
                $setter->setBody(sprintf('return $this->setData(%s, $%s);', "'$propertyName'", $propertyName));
                $classWriter->writeMethod($classFqn, (new PsrPrinter())->printMethod($setter));

                $getter->setBody(sprintf('return $this->getData(%s);', "'$propertyName'"));
                $classWriter->writeMethod($classFqn, (new PsrPrinter())->printMethod($getter));
            }
        } else {
            $table = $input->getArgument('table');
            if (!$table) {
                $table = $io->askQuestion(new Question('Table'));
            }

            $idField = $input->getArgument('id');
            if (!$idField) {
                $idField = $io->askQuestion(new Question('id', 'entity_id'));
            }

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

            $diFilePath = $this->diGenerator->createDiFile(
                $this->nameHelper->getVendor($classFqn),
                $this->nameHelper->getModule($classFqn),
                'global',
                $writer
            );
            $this->diGenerator->addEntityInterfaces($diFilePath, $classFqn, $interfaceFqn);
        }

        return 0;
    }
}
