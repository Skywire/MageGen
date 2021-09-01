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

use MageGen\Helper\NameHelper;
use MageGen\Writer\ModuleFile;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
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
}
