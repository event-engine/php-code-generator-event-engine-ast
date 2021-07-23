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
    public function __invoke(string $json, string $vertexType, string $name): Metadata
    {
        if (empty($json)) {
            $json = '{}';
        }

        switch ($vertexType) {
            case VertexType::TYPE_COMMAND:
                return CommandMetadata::fromJsonMetadata($json, $name);
            case VertexType::TYPE_AGGREGATE:
                return AggregateMetadata::fromJsonMetadata($json, $name);
            case VertexType::TYPE_EVENT:
                return EventMetadata::fromJsonMetadata($json, $name);
            case VertexType::TYPE_DOCUMENT:
                return DocumentMetadata::fromJsonMetadata($json, $name);
            case VertexType::TYPE_UI:
                return UiMetadata::fromJsonMetadata($json, $name);
            case VertexType::TYPE_POLICY:
                return PolicyMetadata::fromJsonMetadata($json, $name);
            case VertexType::TYPE_HOT_SPOT:
                return HotSpotMetadata::fromJsonMetadata($json, $name);
            case VertexType::TYPE_EXTERNAL_SYSTEM:
                return ExternalSystemMetadata::fromJsonMetadata($json, $name);
            case VertexType::TYPE_ROLE:
                return RoleMetadata::fromJsonMetadata($json, $name);
            case VertexType::TYPE_FEATURE:
                return FeatureMetadata::fromJsonMetadata($json, $name);
            case VertexType::TYPE_BOUNDED_CONTEXT:
                return BoundedContextMetadata::fromJsonMetadata($json, $name);
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
