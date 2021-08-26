<?php

/**
 * Copyright Skywire. All rights reserved.
 * See LICENSE.txt for license details.
 *
 * @author      Skywire Core Team
 * @copyright   Copyright (c) 2021 Skywire (http://www.skywire.co.uk)
 */
declare(strict_types=1);

namespace MageGen\Writer;

use Symfony\Component\Filesystem\Filesystem;

abstract class AbstractWriter
{
    /**
     * @var string
     */
    protected $magePath;

    public function __construct(string $magePath)
    {
        $this->fs       = new Filesystem();
        $this->magePath = $magePath;
    }

    protected function getModuleRelativePath($modulePath, $path, $filename)
    {
        return implode('/', array_filter([$modulePath, ($path ?? null), $filename]));
    }
}
