<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Code;

use EventEngine\InspectioGraph\EventType;
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
    private $filterStateMethod;

    /**
     * @var callable
     **/
    private $filterParameterName;

    public function __construct(
        Parser $parser,
        callable $filterStateMethod,
        callable $filterParameterName
    ) {
        $this->parser = $parser;
        $this->filterStateMethod = $filterStateMethod;
        $this->filterParameterName = $filterParameterName;
    }

    public function generate(
        EventType $event
    ): MethodGenerator {
        $methodName = ($this->filterStateMethod)($event->label());

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
