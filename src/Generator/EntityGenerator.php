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

use MageGen\Helper\NameHelper;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;


class EntityGenerator
{
    /**
     * @var NameHelper
     */
    protected $nameHelper;

    public function __construct()
    {
        $this->nameHelper = new NameHelper();
    }

    public function createInterface(string $entityFqn)
    {
        $file = new PhpFile();
        $file->setStrictTypes();

        $interfaceFqn = implode(
            '\\',
            [
                $this->nameHelper->getVendor($entityFqn),
                $this->nameHelper->getModule($entityFqn),
                'Api',
                'Data',
                $this->nameHelper->getClass($entityFqn) . 'Interface',
            ]
        );
        $newNamespace = new PhpNamespace($this->nameHelper->getNamespace($interfaceFqn));
        $class        = ClassType::interface($this->nameHelper->getClass($interfaceFqn));

        $newNamespace->add($class);
        $file->addNamespace($newNamespace);

        return [$file, $interfaceFqn];
    }

    public function createEntity(string $module, string $classFqn, string $interfaceFqn): array
    {
        $modulePrefix = strtolower($module);
        $file         = new PhpFile();
        $file->setStrictTypes();

        $newNamespace = new PhpNamespace($this->nameHelper->getNamespace($classFqn));
        $class        = new ClassType($this->nameHelper->getClass($classFqn));

        $newNamespace->add($class);
        $file->addNamespace($newNamespace);

        $class->addExtend('\Magento\Framework\Model\AbstractModel');
        $class->addImplement('\\' . $interfaceFqn);
        $class->addImplement('\Magento\Framework\DataObject\IdentityInterface');

        $class->addConstant('CACHE_TAG', $modulePrefix . '_entity')->setPublic();
        $class->addProperty('_cacheTag', $modulePrefix . '_entity')->setProtected();
        $class->addProperty('_eventPrefix', $modulePrefix . '_entity')->setProtected();

        $class->addMethod('_construct')->setBody(
            sprintf(
                '$this->_init(\\%s%s);',
                $this->entityFqnToResourceFqn($classFqn),
                '::class'
            )
        )->setProtected();

        $class->addMethod('getIdentities')->setPublic()->setBody('return [self::CACHE_TAG . \'_\' . $this->getId()];');

        return [$file, $classFqn];
    }

    public function createResource(string $entityFqn, string $table, string $idField)
    {
        $file = new PhpFile();
        $file->setStrictTypes();
        $resourceFqn = $this->entityFqnToResourceFqn($entityFqn);

        $newNamespace = new PhpNamespace($this->nameHelper->getNamespace($resourceFqn));
        $class        = new ClassType($this->nameHelper->getClass($resourceFqn));

        $newNamespace->add($class);
        $file->addNamespace($newNamespace);

        $class->addExtend('\Magento\Framework\Model\ResourceModel\Db\AbstractDb');

        $class->addMethod('_construct')->setBody(
            sprintf(
                '$this->_init(%s, %s);',
                "'$table'",
                "'$idField'"
            )
        );

        return [$file, $resourceFqn];
    }

    public function createCollection(string $entityFqn, string $resourceFqn, string $idField)
    {
        $collectionFqn = $resourceFqn . '\\' . 'Collection';
        $file          = new PhpFile();
        $file->setStrictTypes();

        $newNamespace = new PhpNamespace($this->nameHelper->getNamespace($collectionFqn));
        $class        = new ClassType($this->nameHelper->getClass($collectionFqn));

        $newNamespace->add($class);
        $file->addNamespace($newNamespace);

        $class->addExtend('\Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection');

        $class->addProperty('_idFieldName', $idField . '_entity')->setProtected();

        $class->addMethod('_construct')->setBody(
            sprintf(
                '$this->_init(\\%s::class, \\%s::class);',
                $entityFqn,
                $resourceFqn
            )
        );

        return [$file, $collectionFqn];
    }

    protected function entityFqnToResourceFqn(string $entityFqn)
    {
        return implode(
            '\\',
            [
                $this->nameHelper->getNamespace($entityFqn),
                'ResourceModel',
                $this->nameHelper->getClass($entityFqn),
            ]
        );
    }
}
