<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Helper;

use EventEngine\CodeGenerator\EventEngineAst\Metadata\DocumentMetadata;
use EventEngine\InspectioGraph\EventSourcingAnalyzer;
use EventEngine\InspectioGraph\VertexConnection;
use EventEngine\InspectioGraph\VertexType;

trait FindAggregateStateTrait
{
    public function findAggregateState(string $startId, string $direction, EventSourcingAnalyzer $analyzer): ?VertexConnection
    {
        return $analyzer->graph()->findInGraph(
            $startId,
            static function (VertexType $document): bool {
                $metadata = $document->metadataInstance();
                if (! $metadata instanceof DocumentMetadata
                    || $document->type() !== VertexType::TYPE_DOCUMENT
                ) {
                    return false;
                }

                return $metadata->isAggregateState();
            },
            $direction
        );
    }
}
