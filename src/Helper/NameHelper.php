<?php

/**
 * Copyright Skywire. All rights reserved.
 * See LICENSE.txt for license details.
 *
 * @author      Skywire Core Team
 * @copyright   Copyright (c) 2021 Skywire (http://www.skywire.co.uk)
 */
declare(strict_types=1);

namespace MageGen\Helper;

class NameHelper
{

    public function getVendor(string $fqn): string
    {
        $parts = explode('\\', ltrim($fqn, '\\'));

        return $parts[0];
    }

    public function getNamespace(string $fqn): string
    {
        $parts = explode('\\', ltrim($fqn, '\\'));

        return implode('\\', array_slice($parts, 0, count($parts) - 1));
    }

    public function getClass(string $fqn): string
    {
        $parts = explode('\\', ltrim($fqn, '\\'));

        return array_pop($parts);
    }

    public function getModule(string $fqn): string
    {
        $parts = explode('\\', ltrim($fqn, '\\'));

        return $parts[1];
    }

    public function getPath(string $fqn): string
    {
        $parts = explode('\\', ltrim($fqn, '\\'));

        return implode('/', array_slice($parts, 2, -1));
    }
}
