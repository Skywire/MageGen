#!/usr/bin/env php
<?php

/**
 * Copyright Skywire. All rights reserved.
 * See LICENSE.txt for license details.
 *
 * @author      Skywire Core Team
 * @copyright   Copyright (c) 2021 Skywire (http://www.skywire.co.uk)
 */
declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';


use Symfony\Component\Console\Application;

$application = new Application();


$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
]);

$application->add(new \MageGen\MakeModuleCommand($twig));
$application->add(new \MageGen\MakePluginCommand($twig));
$application->add(new \MageGen\MakeEntityCommand($twig));

$application->run();
