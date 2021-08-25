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

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\Parameter;


class MethodHelper
{

    public function createBeforeMethod(ClassType $newClass, string $subjectType, Method $subjectMethod): Method
    {
        $params = $subjectMethod->getParameters();
        array_unshift($params, (new Parameter('subject'))->setType($subjectType));
        $newMethod = $newClass->addMethod('before' . ucfirst($subjectMethod->getName()));
        $newMethod->setParameters($params);
        $newMethod->setBody(
            sprintf(
                "return [%s];",
                implode(
                    ', ',
                    array_map(
                        static function (Parameter $parameter) {
                            return '$' . $parameter->getName();
                        },
                        $subjectMethod->getParameters()
                    )
                )
            )
        );

        return $newMethod;
    }

    public function createAroundMethod(ClassType $newClass, string $subjectType, Method $subjectMethod): Method
    {
        $params = $subjectMethod->getParameters();
        array_unshift($params, (new Parameter('proceed'))->setType('callable'));
        array_unshift($params, (new Parameter('subject'))->setType($subjectType));
        $newMethod = $newClass->addMethod('around' . ucfirst($subjectMethod->getName()));
        $newMethod->setParameters($params);
        $newMethod->setBody(
            sprintf(
                "\$result = \$proceed(%s); \n\nreturn \$result;",
                implode(
                    ', ',
                    array_map(
                        static function (Parameter $parameter) {
                            return '$' . $parameter->getName();
                        },
                        $subjectMethod->getParameters()
                    )
                )
            )
        );

        return $newMethod;
    }

    public function createAfterMethod(ClassType $newClass, string $subjectType, Method $subjectMethod): Method
    {
        $params    = [
            (new Parameter('subject'))->setType($subjectType),
            (new Parameter('result'))->setType($subjectMethod->getReturnType()),
        ];
        $newMethod = $newClass->addMethod('after' . ucfirst($subjectMethod->getName()));
        $newMethod->setParameters($params);
        $newMethod->setBody('return $result;');

        return $newMethod;
    }
}
