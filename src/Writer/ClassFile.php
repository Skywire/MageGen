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

class ClassFile extends AbstractWriter
{
    public function writeMethod(
        string $classFqn,
        string $vendor,
        string $module,
        string $path,
        string $filename,
        string $content
    ) {
        $modulePath = implode('/', [$this->magePath, 'app/code', $vendor, $module]);

        $finalPath = $this->getModuleRelativePath($modulePath, $path, $filename);

        $reflectionClass = new \ReflectionClass($classFqn);

        $methods          = $reflectionClass->getMethods();
        $lastMethod       = array_pop($methods);
        $reflectionMethod = new \ReflectionMethod($classFqn, $lastMethod->getName());

        $file = new \SplFileObject($finalPath, 'ra+');

        $endLine = $reflectionMethod->getEndLine() - 1;
        $file->seek($endLine);
        $position = $file->ftell();
        $file = null;

        $this->injectData($finalPath, "\n\t" . $content, $position);

        return $finalPath;
    }

    protected function injectData($file, $data, $position) {
        $fpFile = fopen($file, "rw+");
        $fpTemp = fopen('php://temp', "rw+");

        $len = stream_copy_to_stream($fpFile, $fpTemp); // make a copy

        fseek($fpFile, $position); // move to the position
        fseek($fpTemp, $position); // move to the position

        fwrite($fpFile, $data); // Add the data

        stream_copy_to_stream($fpTemp, $fpFile); // @Jack

        fclose($fpFile); // close file
        fclose($fpTemp); // close tmp
    }
}
