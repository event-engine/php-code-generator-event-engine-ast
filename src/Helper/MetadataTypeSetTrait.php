<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Helper;

use EventEngine\CodeGenerator\EventEngineAst\Metadata\HasQueryTypeSet;
use EventEngine\CodeGenerator\EventEngineAst\Metadata\HasTypeSet;
use EventEngine\InspectioGraph\VertexType;
use OpenCodeModeling\JsonSchemaToPhp\Type\TypeSet;

trait MetadataTypeSetTrait
{
    public function getMetadataTypeSetFromVertex(VertexType $vertexType): ?TypeSet
    {
        $metadataInstance = $vertexType->metadataInstance();

        $typeSet = null;

        if ($metadataInstance instanceof HasTypeSet) {
            $typeSet = $metadataInstance->typeSet();
        }

        return $typeSet;
    }

    public function getMetadataQueryTypeSetFromVertex(VertexType $vertexType): ?TypeSet
    {
        $metadataInstance = $vertexType->metadataInstance();

        $typeSet = null;

        if ($metadataInstance instanceof HasQueryTypeSet) {
            $typeSet = $metadataInstance->queryTypeSet();
        }

        return $typeSet;
    }
}
