<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine\Code;

use EventEngine\InspectioGraph\AggregateType;
use EventEngine\InspectioGraph\CommandType;
use EventEngine\InspectioGraph\EventType;
use OpenCodeModeling\CodeAst\Code\BodyGenerator;
use OpenCodeModeling\CodeAst\Code\MethodGenerator;
use OpenCodeModeling\CodeAst\Code\ParameterGenerator;
use PhpParser\Parser;

final class AggregateBehaviourCommandMethod
{
    /**
     * @var Parser
     **/
    private $parser;

    /**
     * @var callable
     **/
    private $filterCommandMethodName;

    /**
     * @var callable
     **/
    private $filterParameterName;

    public function __construct(
        Parser $parser,
        callable $filterCommandMethodName,
        callable $filterParameterName
    ) {
        $this->parser = $parser;
        $this->filterCommandMethodName = $filterCommandMethodName;
        $this->filterParameterName = $filterParameterName;
    }

    public function generate(
        AggregateType $aggregate,
        CommandType $command,
        EventType ...$events
    ): MethodGenerator {
        $commandParameterName = ($this->filterParameterName)($command->label());
        $commandMethodName = ($this->filterCommandMethodName)($command->label());

        $params = [new ParameterGenerator($commandParameterName, 'Message')];

        $metadataInstance = $command->metadataInstance();

        if ($metadataInstance === null || false === $metadataInstance->newAggregate()) {
            \array_unshift($params, new ParameterGenerator('state', 'State'));
        }

        $code = '';

        foreach ($events as $event) {
            $code .= \sprintf(
                'yield [Event::%s, $%s->payload()];',
                $event->name(),
                $commandParameterName
            );
        }

        $method = new MethodGenerator(
            $commandMethodName,
            $params,
            MethodGenerator::FLAG_STATIC | MethodGenerator::FLAG_PUBLIC,
            new BodyGenerator(
                $this->parser,
                $code
            )
        );
        $method->setReturnType('Generator');

        return $method;
    }
}
