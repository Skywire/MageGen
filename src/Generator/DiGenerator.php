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
use MageGen\Writer\ModuleFile;
use Twig\Environment;


class DiGenerator
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
    public function createDiFile(string $vendor, string $module, string $area, ModuleFile $writer): string
    {
        $etcPath = 'etc';
        if ($area !== 'global') {
            $etcPath .= '/' . $area;
        }

        return $writer->writeFile(
            $vendor,
            $module,
            $etcPath,
            'di.xml',
            $this->twig->render('module/di.xml.twig')
        );
    }

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

    public function addEntityInterfaces(string $diFilePath, string $entityFqn, string $interfaceFqn)
    {
        $dom                     = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput       = true;
        $dom->loadXML(file_get_contents($diFilePath));

        // Append preference node
        $preferenceNode = $dom->createElement('preference');
        $preferenceNode->setAttribute('for', $interfaceFqn);
        $preferenceNode->setAttribute('type', $entityFqn);
        $dom->documentElement->appendChild($preferenceNode);

        $xml = $dom->saveXML();

        file_put_contents($diFilePath, $xml);
    }


    public function addRepositoryInterfaces(
        string $diFilePath,
        string $repositoryInterfaceFqn,
        string $repositoryFqn,
        string $searchResultInterfaceFqn,
        string $searchResultFqn
    ) {
        $dom                     = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput       = true;
        $dom->loadXML(file_get_contents($diFilePath));

        foreach (
            [
                [$repositoryInterfaceFqn, $repositoryFqn],
                [$searchResultInterfaceFqn, $searchResultFqn],
            ] as $preference
        ) {
            [$interface, $model] = $preference;
            $preferenceNode = $dom->createElement('preference');
            $preferenceNode->setAttribute('for', $interface);
            $preferenceNode->setAttribute('type', $model);
            $dom->documentElement->appendChild($preferenceNode);
        }

        $xml = $dom->saveXML();

        file_put_contents($diFilePath, $xml);
    }
}
