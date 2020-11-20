<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Code;

use OpenCodeModeling\CodeAst\Code\MethodGenerator;
use OpenCodeModeling\CodeAst\Code\ParameterGenerator;

final class DescriptionFileMethod
{
    public static function generate(string $methodName = 'describe'): MethodGenerator
    {
        $method = new MethodGenerator(
            $methodName,
            [
                new ParameterGenerator('eventEngine', 'EventEngine'),
            ]
        );
        $method->setFlags(MethodGenerator::FLAG_STATIC | MethodGenerator::FLAG_PUBLIC);
        $method->setReturnType('void');

        return $method;
    }
}
