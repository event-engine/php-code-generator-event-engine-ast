<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Helper;

use EventEngine\InspectioGraph\EventSourcingAnalyzer;
use EventEngine\InspectioGraph\VertexConnection;
use EventEngine\InspectioGraph\VertexConnectionMap;
use EventEngine\InspectioGraph\VertexType;
use EventEngine\InspectioGraphCody\Aggregate;

trait FindAggregateTrait
{
    public function findAggregate(
        string $startId,
        EventSourcingAnalyzer $analyzer,
        string $direction = null,
        int $maxSteps = 2
    ): ?VertexConnection {
        $walk = $direction ?? VertexConnectionMap::WALK_FORWARD;

        $vertex = $analyzer->graph()->findInGraph(
            $startId,
            static function (VertexType $document): bool {
                return $document instanceof Aggregate;
            },
            $walk,
            $maxSteps
        );

        if ($vertex === null && $direction === null) {
            $walk = VertexConnectionMap::WALK_BACKWARD;

            $vertex = $analyzer->graph()->findInGraph(
                $startId,
                static function (VertexType $document): bool {
                    return $document instanceof Aggregate;
                },
                $walk,
                $maxSteps
            );
        }

        return $vertex;
    }
}
