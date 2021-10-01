<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Helper;

use EventEngine\InspectioGraph\Metadata\HasQuery;
use EventEngine\InspectioGraph\Metadata\HasSchema;
use EventEngine\InspectioGraph\VertexType;

trait MetadataSchemaTrait
{
    /**
     * @param VertexType $vertexType
     * @return mixed|null
     */
    public function getMetadataSchemaFromVertex(VertexType $vertexType)
    {
        $metadataInstance = $vertexType->metadataInstance();

        if (! $metadataInstance instanceof HasSchema) {
            return null;
        }

        return $metadataInstance->schema();
    }

    /**
     * @param VertexType $vertexType
     * @return mixed|null
     */
    public function getMetadataQuerySchemaFromVertex(VertexType $vertexType)
    {
        $metadataInstance = $vertexType->metadataInstance();

        if (! $metadataInstance instanceof HasQuery) {
            return null;
        }

        return $metadataInstance->query();
    }
}
