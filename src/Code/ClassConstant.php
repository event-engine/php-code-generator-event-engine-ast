<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine\Code;

use EventEngine\InspectioGraph\VertexType;
use OpenCodeModeling\CodeAst\Code\ClassConstGenerator;
use OpenCodeModeling\CodeAst\Code\IdentifierGenerator;

final class ClassConstant
{
    /**
     * @var callable
     **/
    private $filterConstName;

    /**
     * @var callable
     **/
    private $filterConstValue;

    public function __construct(callable $filterConstName, callable $filterConstValue)
    {
        $this->filterConstName = $filterConstName;
        $this->filterConstValue = $filterConstValue;
    }

    public function generate(
        VertexType $vertex
    ): IdentifierGenerator {
        $name = ($this->filterConstName)($vertex->label());

        return new IdentifierGenerator(
            $name,
            new ClassConstGenerator(
                $name,
                ($this->filterConstValue)($vertex->label())
            )
        );
    }
}
