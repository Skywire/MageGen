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

class EntityAutocomplete extends AbstractAutocomplete
{
    /** @var string */
    protected $module;

    public function __construct(string $magePath, string $module)
    {
        parent::__construct($magePath);
        $this->module = $module;
    }

    public function getAutocompleteValues(): array
    {
        $entityPath = str_replace('_', '/', $this->module) . '/Model/';
        $searchPath = $this->magePath . '/app/code/' . $entityPath;

        $files = $this->rsearch($searchPath, '/.*php/');

        $entityClasses = [];
        foreach ($files as $file) {
            $pathName  = $file->getPathname();
            $pathName  = str_replace($this->magePath . '/app/code/', '', $pathName);
            $className = str_replace(['.php', '/'], ['', '\\'], $pathName);
            $class     = new \ReflectionClass($className);

            if ($class->isSubclassOf('\\Magento\\Framework\\Model\\AbstractModel')) {
                $entityClasses[] = $class->getShortName();
            }
        }

        return $entityClasses;
    }
}
