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


class AclGenerator
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
    public function createAclFile(string $vendor, string $module, ModuleFile $writer): string
    {
        $etcPath = 'etc';

        return $writer->writeFile(
            $vendor,
            $module,
            $etcPath,
            'acl.xml',
            $this->twig->render('module/acl.xml.twig', ['vendor' => $vendor, 'module' => $module])
        );
    }
}
