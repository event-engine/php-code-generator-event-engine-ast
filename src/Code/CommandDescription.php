<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Code;

use EventEngine\InspectioGraph\CommandType;
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
        CommandType $command,
        ?string $jsonSchemaFilename = null
    ): IdentifierGenerator {
        $commandConstName = ($this->filterConstName)($command->label());

        $code = \sprintf(
            '$eventEngine->registerCommand(self::%s, JsonSchema::object([], [], true));',
            $commandConstName
        );

        if ($jsonSchemaFilename) {
            $code = \sprintf(
                '$eventEngine->registerCommand(
                        self::%s, 
                        JsonSchemaArray::fromFile(self::SCHEMA_PATH . \'%s\')
                    );',
                $commandConstName,
                \substr($jsonSchemaFilename, \strrpos($jsonSchemaFilename, DIRECTORY_SEPARATOR) + 1)
            );
        }

        return new IdentifierGenerator(
            $command->name(),
            new BodyGenerator($this->parser, $code)
        );
    }
}
