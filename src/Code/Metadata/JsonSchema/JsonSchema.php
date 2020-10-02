<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine\Code\Metadata\JsonSchema;

use EventEngine\CodeGenerator\Cartridge\EventEngine\Code\Metadata\JsonSchema\Type\Type;
use EventEngine\CodeGenerator\Cartridge\EventEngine\Code\Metadata\JsonSchema\Type\TypeFactory;
use EventEngine\InspectioGraph\AggregateType;
use EventEngine\InspectioGraph\CommandType;
use EventEngine\InspectioGraph\EventType;
use EventEngine\InspectioGraph\VertexType;

final class JsonSchema
{
    /**
     * @var array
     **/
    private $jsonSchema;

    /**
     * @var Type
     */
    private $type;

    private function __construct(array $jsonSchema)
    {
        $this->jsonSchema = $jsonSchema;

        if (! empty($this->jsonSchema)) {
            $this->type = TypeFactory::createType('', $this->jsonSchema);
            $this->type->setRootSchema(true);
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

    public function type(): ?Type
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function jsonSchema(): array
    {
        return $this->jsonSchema;
    }
}
