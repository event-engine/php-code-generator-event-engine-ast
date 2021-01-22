<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Metadata\InspectioJson;

use EventEngine\CodeGenerator\EventEngineAst\Metadata\HasTypeSet;
use OpenCodeModeling\JsonSchemaToPhp\Shorthand\Shorthand;
use OpenCodeModeling\JsonSchemaToPhp\Type\Type;
use OpenCodeModeling\JsonSchemaToPhp\Type\TypeSet;

final class DocumentMetadata implements \EventEngine\CodeGenerator\EventEngineAst\Metadata\DocumentMetadata, HasTypeSet
{
    private ?array $query;

    private ?string $schema;

    private ?TypeSet $typeSet;

    private function __construct()
    {
    }

    public static function fromJsonMetadata(string $json): self
    {
        $self = new self();
        $self->schema = null;

        $data = MetadataFactory::decodeJson($json);

        if ($data['shorthand'] ?? false) {
            $data['schema'] = Shorthand::convertToJsonSchema($data['schema'] ?? []);
        }
        $self->query = $data['query'] ?? null;

        if (! empty($data['schema'])) {
            $self->schema = MetadataFactory::encodeJson($data['schema']);

            try {
                $self->typeSet = Type::fromDefinition($data['schema']);
            } catch (\OpenCodeModeling\JsonSchemaToPhp\Exception\RuntimeException $e) {
                $self->typeSet = null;
            }
        }

        return $self;
    }

    public function query(): ?array
    {
        return $this->query;
    }

    public function schema(): ?string
    {
        return $this->schema;
    }

    public function typeSet(): ?TypeSet
    {
        return $this->typeSet;
    }
}
