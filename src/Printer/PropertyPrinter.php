<?php

/**
 * Copyright Skywire. All rights reserved.
 * See LICENSE.txt for license details.
 *
 * @author      Skywire Core Team
 * @copyright   Copyright (c) 2021 Skywire (http://www.skywire.co.uk)
 */
declare(strict_types=1);

namespace MageGen\Printer;

use Nette\PhpGenerator\Dumper;
use Nette\PhpGenerator\Helpers;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Printer;
use Nette\PhpGenerator\Property;

class PropertyPrinter extends Printer
{
    /** @var string */
    protected $indentation = '    ';

    /** @var int */
    protected $linesBetweenMethods = 1;

    public function printProperty(Property $property, PhpNamespace $namespace = null)
    {
        $type = $property->getType();
        $def  = (($property->getVisibility() ?: 'public')
            . ($property->isStatic() ? ' static' : '')
            . ($property->isReadOnly() && $type ? ' readonly' : '')
            . ' '
            . ltrim($this->printType($type, $property->isNullable(), $namespace) . ' ')
            . '$' . $property->getName());

        return Helpers::formatDocComment((string)$property->getComment())
            . self::printAttributes($property->getAttributes(), $namespace)
            . $def
            . ($property->getValue() === null && !$property->isInitialized() ? '' : ' = ' . $this->dump(
                    $property->getValue(),
                    strlen($def) + 3
                )) // 3 = ' = '
            . ";\n";
    }

    private function printAttributes(array $attrs, ?PhpNamespace $namespace, bool $inline = false): string
    {
        if (!$attrs) {
            return '';
        }
        $items = [];
        foreach ($attrs as $attr) {
            $args = (new Dumper)->format('...?:', $attr->getArguments());
            $items[] = $this->printType($attr->getName(), false, $namespace) . ($args ? "($args)" : '');
        }
        return $inline
            ? '#[' . implode(', ', $items) . '] '
            : '#[' . implode("]\n#[", $items) . "]\n";
    }
}
