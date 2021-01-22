<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Code;

use EventEngine\CodeGenerator\EventEngineAst\Metadata\CommandMetadata;
use EventEngine\InspectioGraph\AggregateType;
use EventEngine\InspectioGraph\CommandType;
use EventEngine\InspectioGraph\EventType;
use OpenCodeModeling\CodeAst\Code\BodyGenerator;
use OpenCodeModeling\CodeAst\Code\MethodGenerator;
use OpenCodeModeling\CodeAst\Code\ParameterGenerator;
use PhpParser\Parser;

final class AggregateBehaviourEventMethod
{
    /**
     * @var Parser
     **/
    private $parser;

    /**
     * @var callable
     **/
    private $filterEventMethodName;

    /**
     * @var callable
     **/
    private $filterParameterName;

    public function __construct(
        Parser $parser,
        callable $filterEventMethodName,
        callable $filterParameterName
    ) {
        $this->parser = $parser;
        $this->filterEventMethodName = $filterEventMethodName;
        $this->filterParameterName = $filterParameterName;
    }

    public function generate(
        AggregateType $aggregate,
        CommandType $command,
        EventType $event
    ): MethodGenerator {
        $eventParameterName = ($this->filterParameterName)($event->label());
        $eventMethodName = ($this->filterEventMethodName)($event->label());

        $params = [
            new ParameterGenerator($eventParameterName, 'Message'),
        ];
        $methodBody = \sprintf('return State::fromArray($%s->payload());', $eventParameterName);

        $metadataInstance = $command->metadataInstance();

        if ($metadataInstance === null
            || ! $metadataInstance instanceof CommandMetadata
            || false === $metadataInstance->newAggregate()
        ) {
            \array_unshift($params, new ParameterGenerator('state', 'State'));
            $methodBody = \sprintf('return $state->with($%s->payload());', $eventParameterName);
        }

        $method = new MethodGenerator(
            $eventMethodName,
            $params,
            MethodGenerator::FLAG_STATIC | MethodGenerator::FLAG_PUBLIC,
            new BodyGenerator($this->parser, $methodBody)
        );
        $method->setReturnType('State');

        return $method;
    }
}
