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

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ModuleAutocomplete extends AbstractAutocomplete
{
    public function getAutocompleteValues(): array
    {
        $process = new Process(['php', "{$this->magePath}/bin/magento", 'module:status'], $this->magePath);

        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $list = $process->getOutput();

        $list = array_filter(
            explode("\n", $list),
            static function (string $line) {
                return strpos($line, '_') !== false;
            }
        );
        sort($list);

        return $list;
    }
}
