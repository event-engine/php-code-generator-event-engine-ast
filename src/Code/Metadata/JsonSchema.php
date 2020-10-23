<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine\Code\Metadata;

use EventEngine\InspectioGraph\AggregateType;
use EventEngine\InspectioGraph\CommandType;
use EventEngine\InspectioGraph\EventType;
use EventEngine\InspectioGraph\VertexType;
use OpenCodeModeling\JsonSchemaToPhp\Type\Type;
use OpenCodeModeling\JsonSchemaToPhp\Type\TypeSet;

final class JsonSchema
{
    /**
     * @var array
     **/
    private $jsonSchema;

    /**
     * @var TypeSet
     */
    private $typeSet;

    private function __construct(array $jsonSchema)
    {
        $this->jsonSchema = $jsonSchema;

        if (! empty($this->jsonSchema)) {
            $this->typeSet = Type::fromDefinition($this->jsonSchema);
        }
    }

    public static function fromVertex(VertexType $vertex): self
    {
        $metadataInstance = null;
        switch (true) {
            case $vertex instanceof CommandType:
                $metadataInstance = $vertex->metadataInstance();
                break;
            case $vertex instanceof EventType:
                $metadataInstance = $vertex->metadataInstance();
                break;
            case $vertex instanceof AggregateType:
                $metadataInstance = $vertex->metadataInstance();
                break;
            default:
                break;
        }

        if ($metadataInstance && $metadataInstance->schema() !== null) {
            return new self(
                \json_decode($metadataInstance->schema(), true, 512, \JSON_BIGINT_AS_STRING | \JSON_THROW_ON_ERROR)
            );
        }

        return new self([]);
    }

    public static function fromJsonSchema(array $jsonSchema): self
    {
        return new self($jsonSchema);
    }

    public function type(): ?TypeSet
    {
        return $this->typeSet;
    }

    /**
     * @return array
     */
    public function jsonSchema(): array
    {
        return $this->jsonSchema;
    }
}
