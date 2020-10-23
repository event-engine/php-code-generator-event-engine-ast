<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine\Code;

use OpenCodeModeling\CodeAst\Code\IdentifierGenerator;
use OpenCodeModeling\CodeAst\Code\PropertyGenerator;
use OpenCodeModeling\JsonSchemaToPhp\Type\TypeDefinition;

final class ClassProperty
{
    public function generate(
        $name,
        TypeDefinition $type
    ): IdentifierGenerator {
        return new IdentifierGenerator(
            $name,
            new PropertyGenerator(
                $name,
                $type->type()
            )
        );
    }
}
