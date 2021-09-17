<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Helper;

use EventEngine\CodeGenerator\EventEngineAst\Metadata\HasTypeSet;
use EventEngine\InspectioGraph\Metadata\HasCustomData;
use EventEngine\InspectioGraph\VertexType;
use OpenCodeModeling\JsonSchemaToPhp\Type\CustomSupport;

trait MetadataCustomTrait
{
    /**
     * @param VertexType $type
     * @param string $key
     * @param mixed|null $defaultValue
     * @return mixed|null
     */
    private function getCustomMetadata(VertexType $type, string $key, $defaultValue = null)
    {
        $metadataInstance = $type->metadataInstance();

        if (
            $metadataInstance instanceof HasTypeSet
            && ($jsonSchemaTypeSet = $metadataInstance->typeSet())
            && ($jsonSchemaType = $jsonSchemaTypeSet->first())
            && $jsonSchemaType instanceof CustomSupport
        ) {
            return $jsonSchemaType->custom()[$key] ?? $defaultValue;
        } elseif ($metadataInstance instanceof HasCustomData) {
            return $metadataInstance->customData()[$key] ?? $defaultValue;
        }

        return $defaultValue;
    }
}
