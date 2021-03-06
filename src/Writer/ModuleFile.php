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

class ModuleFile extends AbstractWriter
{
    public function writeFile(
        string $vendor,
        string $module,
        string $path,
        string $filename,
        string $content,
        $force = false
    ) {
        $modulePath = implode('/', [$this->magePath, 'app/code', $vendor, $module]);

        $this->createModuleDir($modulePath);

        $finalPath = $this->getModuleRelativePath($modulePath, $path, $filename);

        if (!$this->fs->exists($finalPath) || $force) {
            $this->fs->dumpFile($finalPath, $content);
        }

        return $finalPath;
    }

    protected function createModuleDir($modulePath)
    {
        $dirs = [$modulePath, $modulePath . '/' . 'etc'];
        $this->fs->mkdir($dirs);
    }
}
