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

    /**
     * @var callable
     **/
    private $filterConstValue;

    public function __construct(
        Parser $parser,
        callable $filterConstName,
        callable $filterConstValue
    ) {
        $this->parser = $parser;
        $this->filterConstName = $filterConstName;
        $this->filterConstValue = $filterConstValue;
    }

    public function generate(
        Command $command
    ): IdentifierGenerator {
        $commandConstName = ($this->filterConstName)($command->label());

        $metadata = $command->metadataInstance();

        $code = \sprintf(
            '$eventEngine->registerCommand(self::%s, JsonSchema::object([], [], true));',
            $commandConstName
        );

        if ($metadata !== null) {
            $schema = $metadata->schema();

            if ($schema !== null) {
                $varName = '$' . \lcfirst(($this->filterConstValue)($command->label())) . 'Schema';

                $schemaCode = <<<CODE
%s = <<<'JSONSCHEMA'
%s
JSONSCHEMA;
CODE;

                $code = \sprintf($schemaCode, $varName, $schema) . PHP_EOL;

                $code .= \sprintf(
                    '$eventEngine->registerCommand(
                        self::%s, 
                        new JsonSchemaArray(
                            \json_decode(%s, true, 512, \JSON_THROW_ON_ERROR)
                        )
                    );',
                    $commandConstName,
                    $varName
                );
            }
        }

        return new IdentifierGenerator(
            $command->name(),
            new BodyGenerator($this->parser, $code)
        );
    }
}
