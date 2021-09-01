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
use MageGen\Generator\ExtensionAttributeGenerator;
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
class MakeExtensionAttributeCommand extends AbstractCommand
{
    /**
     * @var Environment
     */
    protected $twig;

    protected static $defaultName = 'make:extension-attribute';

    /**
     * @var ExtensionAttributeGenerator
     */
    protected $extensionAttributeGenerator;

    public function __construct(Environment $twig, string $name = null)
    {
        parent::__construct($twig, $name);

        $this->extensionAttributeGenerator = new ExtensionAttributeGenerator($twig);
    }

    protected function configure(): void
    {
        parent::configure();
        $this->addOption('magepath', 'm', InputOption::VALUE_REQUIRED, 'Path to Magento installation', getcwd());
        $this->addArgument('module', InputArgument::OPTIONAL, 'Module name');
        $this->addArgument('for', InputArgument::OPTIONAL, 'Target class / interface');
        $this->addArgument('attribute_code', InputArgument::OPTIONAL, 'Attribute code');
        $this->addArgument('attribute_type', InputArgument::OPTIONAL, 'Attribute type (A scalar, interface or class)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        require $input->getOption('magepath') . '/vendor/autoload.php';

        $io = new SymfonyStyle($input, $output);

        $module = $this->getModuleAnswer($input, $io);

        $target = $input->getArgument('for');
        if (!$target) {
            $target = $io->askQuestion(
                (new Question('For'))
            );
        }

        $attributeCode = $input->getArgument('attribute_code');
        if (!$attributeCode) {
            $attributeCode = $io->askQuestion(
                (new Question('attribute_code'))
            );
        }

        $attributeType = $input->getArgument('attribute_type');
        if (!$attributeType) {
            $attributeType = $io->askQuestion(
                (new Question('attribute_type'))
            );
        }
        [$vendor, $module] = explode('_', $module);

        $writer            = $this->getWriter($input);
        $attributeFilePath = $this->extensionAttributeGenerator->createAttributeFile(
            $vendor,
            $module,
            $writer
        );

        $this->extensionAttributeGenerator->addAttribute($attributeFilePath, $target, $attributeCode, $attributeType);

        return 0;
    }
}
