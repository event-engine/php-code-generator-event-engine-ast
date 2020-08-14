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
use OpenCodeModeling\CodeAst\Code\MethodGenerator;
use PhpParser\Parser;

final class AggregateStateMethod
{
    /**
     * @var Parser
     **/
    private $parser;

    /**
     * @var callable
     **/
    private $filterMethodName;

    public function __construct(
        Parser $parser,
        callable $filterCommandMethodName
    ) {
        $this->parser = $parser;
        $this->filterMethodName = $filterCommandMethodName;
    }

    public function generate(
        Event $event
    ): MethodGenerator {
        $methodName = ($this->filterMethodName)($event->label());

        // TODO wrong name and missing parameterts, withUserCheckedIn should be withCheckedInUser

        $method = new MethodGenerator(
            $methodName,
            [],
            MethodGenerator::FLAG_PUBLIC,
            new BodyGenerator(
                $this->parser,
                '$instance = clone $this;' . PHP_EOL . 'return $instance;'
            )
        );
        $method->setReturnType('self');

        return $method;
    }
}
