<?php

/**
 * Copyright Skywire. All rights reserved.
 * See LICENSE.txt for license details.
 *
 * @author      Skywire Core Team
 * @copyright   Copyright (c) 2021 Skywire (http://www.skywire.co.uk)
 */
declare(strict_types=1);

namespace MageGen\Generator;

use MageGen\Writer\ModuleFile;
use Twig\Environment;


class SchemaPatchGenerator
{
    /**
     * @var Environment
     */
    protected $twig;

    /**
     * DiGenerator constructor.
     *
     * @param Environment $twig
     */
    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * Create a file if it doesn't exist and return the path
     *
     * @param string     $vendor
     * @param string     $module
     * @param ModuleFile $writer
     *
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function createSchemaPatch(string $vendor, string $module, string $patchName, ModuleFile $writer): string
    {
        return $writer->writeFile(
            $vendor,
            $module,
            'Setup/Patch/Schema',
            $patchName . '.php',
            $this->twig->render('Setup/Patch/Schema/Patch.php.twig', ['vendor' => $vendor, 'module' => $module, 'patch_name' => $patchName])
        );
    }
}
