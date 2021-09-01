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
use Nette\PhpGenerator\ClassType;
use Twig\Environment;


class ExtensionAttributeGenerator
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
    public function createAttributeFile(string $vendor, string $module, ModuleFile $writer): string
    {
        $etcPath = 'etc';

        return $writer->writeFile(
            $vendor,
            $module,
            $etcPath,
            'extension_attributes.xml',
            $this->twig->render('module/extension_attributes.xml.twig')
        );
    }

    public function addAttribute(
        string $attributeFilePath,
        string $target,
        string $attributeCode,
        string $attributeType
    ) {
        $dom                     = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput       = true;
        $dom->loadXML(file_get_contents($attributeFilePath));

        $targetNode = $this->getTargetNode($dom, $target);
        if (!$targetNode) {
            $targetNode = $dom->createElement('extension_attributes');
            $targetNode->setAttribute('for', $target);
            $dom->documentElement->appendChild($targetNode);
        }

        // Add attribute
        $attributeNode = $dom->createElement('attribute');
        $attributeNode->setAttribute('code', $attributeCode);
        $attributeNode->setAttribute('type', $attributeType);
        $targetNode->appendChild($attributeNode);

        $xml = $dom->saveXML();

        file_put_contents($attributeFilePath, $xml);
    }

    protected function getTargetNode(DOMDocument $dom, string $target): ?DOMElement
    {
        $tables = $dom->getElementsByTagName('extension_attributes');

        $tables = array_filter(
            iterator_to_array($tables),
            function (DOMElement $node) use ($target) {
                return $node->getAttribute('for') === $target;
            }
        );

        if (empty($tables)) {
            return null;
        }

        return array_shift($tables);
    }
}
