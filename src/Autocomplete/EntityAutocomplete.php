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

use MageGen\Helper\NameHelper;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class EntityAutocomplete
{
    public function getAutocompleteValues(string $magePath, string $module): array
    {
        $entityPath = str_replace('_', '/', $module) . '/Model/';
        $searchPath = $magePath . '/app/code/' . $entityPath;

        $files = $this->rsearch($searchPath, '/.*php/');

        $entityClasses = [];
        foreach ($files as $file) {
            $pathName  = $file->getPathname();
            $pathName  = str_replace($magePath . '/app/code/', '', $pathName);
            $className = str_replace(['.php', '/'], ['', '\\'], $pathName);
            $class     = new \ReflectionClass($className);

            if ($class->isSubclassOf('\\Magento\\Framework\\Model\\AbstractModel')) {
                $entityClasses[] = $class->getShortName();
            }
        }

        return $entityClasses;
    }

    protected function rsearch($dir, $pattern)
    {
        if(!is_readable($dir)) {
            return [];
        }
        $dir   = new RecursiveDirectoryIterator($dir);
        $ite   = new RecursiveIteratorIterator($dir);
        $files = new RegexIterator($ite, $pattern, RegexIterator::MATCH);

        return iterator_to_array($files);
    }
}
