<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine\Code;

use EventEngine\InspectioGraph\Event;
use OpenCodeModeling\CodeAst\Code\BodyGenerator;
use OpenCodeModeling\CodeAst\Code\IdentifierGenerator;
use PhpParser\Parser;

final class EventDescription
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
        Event $event
    ): IdentifierGenerator {
        $eventConstName = ($this->filterConstName)($event->label());

        $code = \sprintf(
            '$eventEngine->registerEvent(self::%s, JsonSchema::object([], [], true));',
            $eventConstName
        );

        return new IdentifierGenerator(
            $event->name(),
            new BodyGenerator($this->parser, $code)
        );
    }
}
