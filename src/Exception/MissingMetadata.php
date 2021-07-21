<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Exception;

use EventEngine\InspectioGraph\VertexType;

final class MissingMetadata extends RuntimeException
{
    public static function forVertex(VertexType $vertexType, string $metadataClassName): self
    {
        return new self(
            \sprintf(
                'Cannot generate %s "%s" due missing metadata of type %s',
                $vertexType->type(),
                $vertexType->name(),
                $metadataClassName
            )
        );
    }
}
