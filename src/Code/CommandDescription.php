<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine\Code;

use EventEngine\InspectioGraph\Command;
use OpenCodeModeling\CodeAst\Code\BodyGenerator;
use OpenCodeModeling\CodeAst\Code\IdentifierGenerator;
use PhpParser\Parser;

final class CommandDescription
{
    /**
     * @var Parser
     **/
    private $parser;

    /**
     * @var callable
     **/
    private $filterConstName;

    public function __construct(
        Parser $parser,
        callable $filterConstName
    ) {
        $this->parser = $parser;
        $this->filterConstName = $filterConstName;
    }

    public function generate(
        Command $command
    ): IdentifierGenerator {
        $commandConstName = ($this->filterConstName)($command->label());

        $code = \sprintf(
            '$eventEngine->registerCommand(self::%s, JsonSchema::object([], [], true));',
            $commandConstName
        );

        return new IdentifierGenerator(
            $command->name(),
            new BodyGenerator($this->parser, $code)
        );
    }
}
