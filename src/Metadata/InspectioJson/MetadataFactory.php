<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Metadata\InspectioJson;

use EventEngine\CodeGenerator\EventEngineAst\Exception\RuntimeException;
use EventEngine\InspectioGraph\Metadata\Metadata;
use EventEngine\InspectioGraph\VertexType;

final class MetadataFactory
{
    public function __invoke(string $json, string $vertexType): Metadata
    {
        if (empty($json)) {
            $json = '{}';
        }

        switch ($vertexType) {
            case VertexType::TYPE_COMMAND:
                return CommandMetadata::fromJsonMetadata($json);
            case VertexType::TYPE_AGGREGATE:
                return AggregateMetadata::fromJsonMetadata($json);
            case VertexType::TYPE_EVENT:
                return EventMetadata::fromJsonMetadata($json);
            case VertexType::TYPE_DOCUMENT:
                return DocumentMetadata::fromJsonMetadata($json);
            default:
                throw new RuntimeException(
                    \sprintf(
                        'Given type "%s" is not supported. See \EventEngine\InspectioGraph\VertexType::TYPE_* constants.',
                        $vertexType
                    )
                );
        }
    }

    public static function decodeJson(string $json): array
    {
        return \json_decode($json, true, 512, \JSON_BIGINT_AS_STRING | \JSON_THROW_ON_ERROR);
    }

    public static function encodeJson(array $json): string
    {
        $flags = \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_PRESERVE_ZERO_FRACTION | \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT;

        return \json_encode($json, $flags);
    }
}
