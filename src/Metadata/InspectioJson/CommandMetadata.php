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

final class CommandMetadata implements \EventEngine\CodeGenerator\EventEngineAst\Metadata\CommandMetadata, HasTypeSet
{
    use JsonMetadataTrait;

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

    public function newAggregate(): bool
    {
        return $this->customData['newAggregate'] ?? false;
    }
}
