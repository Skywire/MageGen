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

class ModuleFile
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

    public function writeFile(string $namespace, string $module, string $path, string $filename, string $content)
    {
        $modulePath = implode('/', [$this->magePath, 'app/code', $namespace, $module]);

        $this->createModuleDir($modulePath);

        $finalPath = $this->getModuleRelativePath($modulePath, $path, $filename);
        $this->fs->dumpFile($finalPath, $content);

        return $finalPath;
    }

    protected function createModuleDir($modulePath)
    {
        $dirs = [$modulePath, $modulePath . '/' . 'etc'];
        $this->fs->mkdir($dirs);
    }

    protected function getModuleRelativePath($modulePath, $path, $filename)
    {
        return implode('/', array_filter([$modulePath, ($path ?? null), $filename]));
    }
}
