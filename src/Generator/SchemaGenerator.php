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


class SchemaGenerator
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
    public function createSchemaFile(string $vendor, string $module, ModuleFile $writer): string
    {
        $etcPath = 'etc';

        return $writer->writeFile(
            $vendor,
            $module,
            $etcPath,
            'db_schema.xml',
            $this->twig->render('module/db_schema.xml.twig')
        );
    }

    public function addEntity(string $schemaFilePath, string $classFqn)
    {
        $dom                     = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput       = true;
        $dom->loadXML(file_get_contents($schemaFilePath));

        $resourceFqn = str_replace('\\Model\\', '\\Model\\ResourceModel\\', $classFqn);
        [$tableName, $idField] = $this->getTableAndIdField($resourceFqn);
        if (!$tableName) {
            throw new \RuntimeException("Table name and id could not be determined for resource '$resourceFqn'");
        }

        $tableNode = $this->getTableNode($dom, $tableName);
        if (!$tableNode) {
            $tableNode = $dom->createElement('table');
            $tableNode->setAttribute('name', $tableName);
            $tableNode->setAttribute('resource', 'default');
            $tableNode->setAttribute('engine', 'innodb');
            $dom->documentElement->appendChild($tableNode);

            // Add primary key
            $primaryNode = $dom->createElement('column');
            $primaryNode->setAttribute('xsi:type', 'int');
            $primaryNode->setAttribute('name', $idField);
            $primaryNode->setAttribute('unsigned', 'true');
            $primaryNode->setAttribute('nullable', 'false');
            $primaryNode->setAttribute('identity', 'true');
            $primaryNode->setAttribute('comment', 'Primary Key');
            $tableNode->appendChild($primaryNode);

            $constraintNode = $dom->createElement('constraint');
            $constraintNode->setAttribute('xsi:type', 'primary');
            $constraintNode->setAttribute('referenceId', 'PRIMARY');
            $constraintColumnNode = $dom->createElement('column');
            $constraintColumnNode->setAttribute('name', $idField);
            $constraintNode->appendChild($constraintColumnNode);
            $tableNode->appendChild($constraintNode);
        }

        // TODO Add properties

//        $preferenceNode = $dom->createElement('preference');
//        $preferenceNode->setAttribute('for', $interface);
//        $preferenceNode->setAttribute('type', $model);
//        $dom->documentElement->appendChild($preferenceNode);

        $xml = $dom->saveXML();

        file_put_contents($schemaFilePath, $xml);
    }

    protected function getTableNode(DOMDocument $dom, string $tableName): ?DOMElement
    {
        $tables = $dom->getElementsByTagName('table');

        $tables = array_filter(
            iterator_to_array($tables),
            function (DOMElement $node) use($tableName) {
                return $node->getAttribute('name') === $tableName;
            }
        );

        if (empty($tables)) {
            return null;
        }

        return array_shift($tables);
    }

    protected function getTableAndIdField(string $resourceFqn): array
    {
        $resourceClass = ClassType::withBodiesFrom($resourceFqn);
        $body          = $resourceClass->getMethod('_construct')->getBody();

        preg_match_all("/'\w*'/", $body, $matches);

        if (!empty($matches)) {
            return array_map(
                function (string $match) {
                    return trim($match, "'");
                },
                $matches[0]
            );
        }

        return [];
    }
}
