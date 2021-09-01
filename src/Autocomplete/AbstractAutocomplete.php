<?php

/**
 * Copyright Skywire. All rights reserved.
 * See LICENSE.txt for license details.
 *
 * @author      Skywire Core Team
 * @copyright   Copyright (c) 2021 Skywire (http://www.skywire.co.uk)
 */
declare(strict_types=1);

namespace MageGen\Autocomplete;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

abstract class AbstractAutocomplete
{
    /** @var string */
    protected $magePath;

    public function __construct($magePath)
    {
        $this->magePath = $magePath;
    }

    abstract public function getAutocompleteValues(): array;

    public function rsearch($dir, $pattern)
    {
        if (!is_readable($dir)) {
            return [];
        }
        $dir   = new RecursiveDirectoryIterator($dir);
        $ite   = new RecursiveIteratorIterator($dir);
        $files = new RegexIterator($ite, $pattern, RegexIterator::MATCH);

        return iterator_to_array($files);
    }
}
