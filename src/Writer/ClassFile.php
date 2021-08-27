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

use MageGen\Helper\NameHelper;
use Symfony\Component\Filesystem\Filesystem;

class ClassFile extends AbstractWriter
{
    public function writeMethod(
        string $classFqn,
        string $content
    ) {
        $finalPath = $this->fqnToPath($classFqn);

        $reflectionClass = new \ReflectionClass($classFqn);

        $methods          = $reflectionClass->getMethods();
        $lastMethod       = array_pop($methods);
        $reflectionMethod = new \ReflectionMethod($classFqn, $lastMethod->getName());

        $file = new \SplFileObject($finalPath, 'ra+');

        $endLine = $reflectionMethod->getEndLine() - 1;
        $file->seek($endLine);
        $position = $file->ftell();
        $file     = null;

        $this->injectData($finalPath, "\n\t" . $content, $position);
    }

    public function writeProperty(
        string $classFqn,
        string $content
    ) {
        $finalPath = $this->fqnToPath($classFqn);

        $reflectionClass = new \ReflectionClass($classFqn);

        // Does reflection allow me to get property line numbers? Does it hell.
        // Properties go before methods so let's inject before the first method instead.
        $methods          = $reflectionClass->getMethods();
        if(!empty($methods)) {
            $firstMethod      = array_shift($methods);
            $reflectionMethod = new \ReflectionMethod($classFqn, $firstMethod->getName());

            $startLine = $reflectionMethod->getStartLine() - 3;
        } else {
            $startLine = $reflectionClass->getEndLine() - 2;
        }

        $file = new \SplFileObject($finalPath, 'ra+');
        $file->seek($startLine);
        $position = $file->ftell();
        $file     = null;

        $this->injectData($finalPath, "\t" . $content, $position);
    }

    protected function fqnToPath(string $classFqn)
    {
        $helper = new NameHelper();

        $modulePath = implode(
            '/',
            [
                $this->magePath,
                'app/code',
                $helper->getVendor($classFqn),
                $helper->getModule(
                    $classFqn
                ),
            ]
        );

        $finalPath = $this->getModuleRelativePath(
            $modulePath,
            $helper->getPath($classFqn),
            $helper->getClass($classFqn) . '.php'
        );

        return $finalPath;
    }

    protected function injectData($file, $data, $position)
    {
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
