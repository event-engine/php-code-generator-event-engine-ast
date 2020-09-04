<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine\NodeVisitor\Exception;

use EventEngine\InspectioGraph\VertexType;
use RuntimeException;

class WrongVertexType extends RuntimeException
{
    /**
     * @var VertexType
     */
    private $vertex;

    public static function withVertex(VertexType $vertex, string $expectedType): self
    {
        $self = new self(
            \sprintf('Provided vertex with type "%s" does not match expected type "%s"', $vertex->type(), $expectedType)
        );
        $self->vertex = $vertex;

        return $self;
    }

    public function vertex(): VertexType
    {
        return $this->vertex;
    }
}
