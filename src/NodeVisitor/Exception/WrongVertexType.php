<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine\NodeVisitor\Exception;

use EventEngine\InspectioGraph\Vertex;
use RuntimeException;

class WrongVertexType extends RuntimeException
{
    /**
     * @var Vertex
     */
    private $vertex;

    public static function withVertex(Vertex $vertex, string $expectedType): self
    {
        $self = new self(
            \sprintf('Provided vertex with type "%s" does not match expected type "%s"', $vertex->type(), $expectedType)
        );
        $self->vertex = $vertex;

        return $self;
    }

    public function vertex(): Vertex
    {
        return $this->vertex;
    }
}
