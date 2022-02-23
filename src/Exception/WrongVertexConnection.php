<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Exception;

use EventEngine\InspectioGraph\VertexConnection;

final class WrongVertexConnection extends RuntimeException
{
    public static function forConnection(VertexConnection $connection, $expectedType): self
    {
        return new self(
            \sprintf(
                'The identity (%s - %s) of the connection has type "%s" but expecting type "%s" in order to generate code.',
                $connection->identity()->id(),
                $connection->identity()->name(),
                $connection->identity()->type(),
                $expectedType
            )
        );
    }
}
