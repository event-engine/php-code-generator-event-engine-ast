<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine\Code\Metadata\JsonSchema\Type;

final class TypeFactory
{
    public static function createType($name, array $definition): Type
    {
        if (! isset($definition['type'])) {
            if (isset($definition['$ref'])) {
                return ReferenceType::fromDefinition($name, $definition);
            }

            throw new \RuntimeException(\sprintf('The "type" is missing in schema definition for "%s"', $name));
        }

        $definitionTypes = (array) $definition['type'];

        $types = [];

        $isNullable = false;

        foreach ($definitionTypes as $jsonType) {
            switch ($jsonType) {
                case 'string':
                    $types[] = StringType::fromDefinition($name, $definition);
                    break;
                case 'number':
                    $types[] = NumberType::fromDefinition($name, $definition);
                    break;
                case 'integer':
                    $types[] = IntegerType::fromDefinition($name, $definition);
                    break;
                case 'boolean':
                    $types[] = BooleanType::fromDefinition($name, $definition);
                    break;
                case 'object':
                    $types[] = ObjectType::fromDefinition($name, $definition);
                    break;
                case 'array':
                    $types[] = ArrayType::fromDefinition($name, $definition);
                    break;
                case 'null':
                case 'Null':
                case 'NULL':
                    $isNullable = true;
                    break;
                default:
                    throw new \RuntimeException(
                        \sprintf('JSON schema type "%s" is not implemented', $definition['type'])
                    );
            }
        }

        if (\count($types) === 0) {
            throw new \RuntimeException('Could not determine type of JSON schema');
        }

        $types[0]->setNullable($isNullable);

        // TODO support multiple types
        return $types[0];
    }
}
