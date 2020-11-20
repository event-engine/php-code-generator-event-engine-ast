<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Code;

use EventEngine\InspectioGraph\AggregateType;
use EventEngine\InspectioGraph\CommandType;
use EventEngine\InspectioGraph\EventType;
use OpenCodeModeling\CodeAst\Code\BodyGenerator;
use OpenCodeModeling\CodeAst\Code\IdentifierGenerator;
use PhpParser\Parser;

final class AggregateDescription
{
    /**
     * @var Parser
     **/
    private $parser;

    /**
     * @var callable
     **/
    private $filterConstName;

    /**
     * @var callable
     **/
    private $filterAggregateId;

    /**
     * @var callable
     **/
    private $filterCommandMethodName;

    /**
     * @var callable
     **/
    private $filterEventMethodName;

    public function __construct(
        Parser $parser,
        callable $filterConstName,
        callable $filterAggregateId,
        callable $filterCommandMethodName,
        callable $filterEventMethodName
    ) {
        $this->parser = $parser;
        $this->filterConstName = $filterConstName;
        $this->filterAggregateId = $filterAggregateId;
        $this->filterCommandMethodName = $filterCommandMethodName;
        $this->filterEventMethodName = $filterEventMethodName;
    }

    public function generate(
        string $aggregateBehaviourCommandClassName,
        string $aggregateBehaviourEventClassName,
        ?string $storeStateIn,
        CommandType $command,
        AggregateType $aggregate,
        EventType ...$events
    ): IdentifierGenerator {
        $commandConstName = ($this->filterConstName)($command->label());
        $commandMethodName = ($this->filterCommandMethodName)($command->label());
        $aggregateName = ($this->filterConstName)($aggregate->label());
        $identifiedBy = ($this->filterAggregateId)($aggregate->label());

        $metadataInstance = $command->metadataInstance();

        $with = $metadataInstance && true === $metadataInstance->newAggregate() ? 'withNew' : 'withExisting';

        $code = \sprintf('$eventEngine->process(Command::%s)->%s(self::%s)', $commandConstName, $with, $aggregateName);
        $code .= \sprintf("->identifiedBy('%s')->handle([%s::class, '%s'])", $identifiedBy, $aggregateBehaviourCommandClassName, $commandMethodName);

        $recordThatName = 'recordThat';

        foreach ($events as $event) {
            $eventMethodName = ($this->filterEventMethodName)($event->label());
            $eventConstName = ($this->filterConstName)($event->label());

            $code .= \sprintf("->%s(Event::%s)->apply([%s::class, '%s'])", $recordThatName, $eventConstName, $aggregateBehaviourEventClassName, $eventMethodName);
            $recordThatName = 'orRecordThat';
        }
        $metadataInstance = $command->metadataInstance();

        $code .= $metadataInstance && true === $metadataInstance->newAggregate() && $storeStateIn ? \sprintf("->storeStateIn('%s');", $storeStateIn) : '';

        $code .= ';';

        return new IdentifierGenerator(
            $command->name(),
            new BodyGenerator($this->parser, $code)
        );
    }
}
