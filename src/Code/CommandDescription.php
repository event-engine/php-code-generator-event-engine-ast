<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine\Code;

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
                        new JsonSchemaArray(
                            \json_decode(file_get_contents(\'%s\'), true, 512, \JSON_THROW_ON_ERROR)
                        )
                    );',
                $commandConstName,
                $jsonSchemaFilename
            );
        }

        return new IdentifierGenerator(
            $command->name(),
            new BodyGenerator($this->parser, $code)
        );
    }
}
