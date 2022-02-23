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
    public function findAggregateState(
        string $startId,
        string $direction,
        EventSourcingAnalyzer $analyzer
    ): ?VertexConnection {
        $connection = $analyzer->connection($startId);

        if (true === $this->isAggregateState($connection->identity())) {
            return $connection;
        }

        return $analyzer->graph()->findInGraph(
            $startId,
            fn (VertexType $document): bool => $this->isAggregateState($document),
            $direction
        );
    }

    private function isAggregateState(VertexType $document): bool
    {
        $metadata = $document->metadataInstance();
        if (! $metadata instanceof DocumentMetadata
            || $document->type() !== VertexType::TYPE_DOCUMENT
        ) {
            return false;
        }

        return $metadata->isAggregateState();
    }

    public function getAggregateStateCollectionName(
        string $startId,
        string $direction,
        EventSourcingAnalyzer $analyzer,
        callable $filterConstValue
    ): ?string {
        if ($aggregateState = $this->findAggregateState($startId, $direction, $analyzer)) {
            $aggregateStateMetadata = $aggregateState->identity()->metadataInstance();

            $defaultName = ($filterConstValue)($aggregateState->identity()->label());

            if ($aggregateStateMetadata instanceof DocumentMetadata
            ) {
                return $aggregateStateMetadata->customData()['collection'] ?? $defaultName;
            }

            return $defaultName;
        }

        return null;
    }
}
