<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Metadata\InspectioJson;

use EventEngine\CodeGenerator\EventEngineAst\Metadata\HasTypeSet;
use OpenCodeModeling\JsonSchemaToPhp\Type\TypeSet;

final class DocumentMetadata implements \EventEngine\CodeGenerator\EventEngineAst\Metadata\DocumentMetadata, HasTypeSet
{
    use JsonMetadataTrait;

    private ?array $query;

    private function __construct()
    {
    }

    public function schema(): ?array
    {
        return $this->schema;
    }

    public function typeSet(): ?TypeSet
    {
        return $this->typeSet;
    }

    public function customData(): array
    {
        return $this->customData;
    }

    public function query(): ?array
    {
        return $this->customData['query'] ?? null;
    }

    public function isAggregateState(): bool
    {
        return $this->customData['aggregate_state'] ?? false;
    }
}
