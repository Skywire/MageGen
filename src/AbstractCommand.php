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
use MageGen\Helper\NameHelper;
use MageGen\Writer\ModuleFile;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;
use Twig\Environment;

/**
 * @method string getCommandDescription()
 */
abstract class AbstractCommand extends Command
{
    /**
     * @var Environment
     */
    protected $twig;

    /**
     * @var NameHelper
     */
    protected $nameHelper;

    public function __construct(Environment $twig, string $name = null)
    {
        parent::__construct($name);
        $this->twig       = $twig;
        $this->nameHelper = new NameHelper();
    }

    protected function getWriter(InputInterface $input)
    {
        return new ModuleFile($input->getOption('magepath'));
    }

    protected function getModuleAnswer(InputInterface $input, $io)
    {
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

        return $module;
    }
}
