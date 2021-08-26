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

use DOMDocument;
use DOMElement;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\Parameter;
use Sabre\Xml\Service;
use XMLWriter;


class DiGenerator
{

    public function addPlugin(string $diFilePath, string $subject, string $classFqn, string $type)
    {
        $subject  = ltrim($subject, '\\');
        $classFqn = ltrim($classFqn, '\\');

        $dom                     = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput       = true;
        $dom->loadXML(file_get_contents($diFilePath));

        $types = $dom->getElementsByTagName('type');

        $matchingNodes = array_filter(
            iterator_to_array($types),
            static function (DOMElement $node) use ($subject) {
                return $node->getAttribute('name') == $subject;
            }
        );

        if (empty($matchingNodes)) {
            // Create new node
            $typeNode = $dom->createElement('type');
            $typeNode->setAttribute('name', $subject);
            $dom->documentElement->appendChild($typeNode);
        } else {
            $typeNode = $matchingNodes[0];
        }

        // check plugin doesn't already exist for this plugin class
        $existingPluginNodes = array_filter(
            iterator_to_array($typeNode->getElementsByTagName('plugin')),
            static function (DOMElement $node) use ($classFqn) {
                return $node->getAttribute('type') == $classFqn;
            }
        );

        if (!empty($existingPluginNodes)) {
            // Plugin already exists, bail out
            return;
        }

        // Append plugin node
        $pluginNode = $dom->createElement('plugin');
        $pluginNode->setAttribute('name', str_replace('\\', '', $classFqn . ucfirst($type)));
        $pluginNode->setAttribute('type', $classFqn);
        $typeNode->appendChild($pluginNode);

        $xml = $dom->saveXML();

        file_put_contents($diFilePath, $xml);
    }
}
