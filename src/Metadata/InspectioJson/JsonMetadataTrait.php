<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Metadata\InspectioJson;

use EventEngine\CodeGenerator\EventEngineAst\Exception\ErrorParsingMetadata;
use EventEngine\CodeGenerator\EventEngineAst\Metadata\HasTypeSet;
use EventEngine\InspectioGraph\Metadata\HasSchema;
use OpenCodeModeling\JsonSchemaToPhp\Exception\RuntimeException;
use OpenCodeModeling\JsonSchemaToPhp\Shorthand\Shorthand;
use OpenCodeModeling\JsonSchemaToPhp\Type\Type;
use OpenCodeModeling\JsonSchemaToPhp\Type\TypeSet;

trait JsonMetadataTrait
{
    private ?array $schema = null;

    private ?TypeSet $typeSet = null;

    private array $customData = [];

    /**
     * @param string $json
     * @param string $name
     * @return static
     * @throws \JsonException
     */
    public static function fromJsonMetadata(string $json, string $name)
    {
        $self = new self();

        $data = MetadataFactory::decodeJson($json);

        $self->customData = $data;

        if (! $self instanceof HasSchema) {
            return $self;
        }

        unset($self->customData['schema'], $self->customData['shorthand']);

        $schema = $data['schema'] ?? null;

        if ($schema !== null && ($data['shorthand'] ?? false)) {
            $schema = Shorthand::convertToJsonSchema($schema, $self->customData);
        }

        $self->schema = $schema;

        if (! empty($self->schema) && $self instanceof HasTypeSet) {
            if (! isset($self->schema['name'])) {
                $self->schema['name'] = $name;
            }

            try {
                $self->typeSet = Type::fromDefinition($self->schema);
            } catch (RuntimeException $e) {
                throw ErrorParsingMetadata::withError(
                    \sprintf(
                        'Could not create JSON schema type set from JSON schema definition. Error: %s (%s:%d)',
                        $e->getMessage(), $e->getFile(), $e->getLine()
                    ),
                    $e
                );
            }
        }

        return $self;
    }
}
