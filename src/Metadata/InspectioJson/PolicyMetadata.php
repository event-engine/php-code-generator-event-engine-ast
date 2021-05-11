<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Metadata\InspectioJson;

final class PolicyMetadata implements \EventEngine\CodeGenerator\EventEngineAst\Metadata\PolicyMetadata
{
    use JsonMetadataTrait;

    private function __construct()
    {
    }

    public function customData(): array
    {
        return $this->customData;
    }

    public function streams(): array
    {
        return $this->customData['streams'] ?? [];
    }
}
