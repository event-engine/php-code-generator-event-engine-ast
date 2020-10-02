<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine;

use EventEngine\CodeGenerator\Cartridge\EventEngine\Code\Metadata\JsonSchema\JsonSchema;
use EventEngine\CodeGenerator\Cartridge\EventEngine\Code\Metadata\JsonSchema\Type\ObjectType;
use EventEngine\CodeGenerator\Cartridge\EventEngine\Code\Metadata\JsonSchema\Type\ReferenceType;
use EventEngine\CodeGenerator\Cartridge\EventEngine\Exception\RuntimeException;
use EventEngine\InspectioGraph\EventSourcingAnalyzer;
use OpenCodeModeling\CodeAst\Code\PropertyGenerator;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\PrettyPrinterAbstract;

final class CommandProperty
{
    /**
     * @var Parser
     **/
    private $parser;

    /**
     * @var PrettyPrinterAbstract
     */
    private $printer;

    public function __construct(
        Parser $parser,
        PrettyPrinterAbstract $printer
    ) {
        $this->parser = $parser;
        $this->printer = $printer;
    }

    public function __invoke(
        EventSourcingAnalyzer $analyzer,
        array $files
    ): array {
        foreach ($analyzer->commandMap() as $name => $command) {
            $name = $command->name();

            if (! isset($files[$name])) {
                continue;
            }
            $jsonSchema = JsonSchema::fromVertex($command);

            $type = $jsonSchema->type();

            if ($type instanceof ReferenceType) {
                $type = $type->getResolvedType();
            }

            if ($type === null) {
                continue;
            }

            if (! $type instanceof ObjectType) {
                throw new RuntimeException(
                    \sprintf(
                        'Need type of "%s", type "%s" given',
                        ObjectType::class,
                        \get_class($type)
                    )
                );
            }

            $properties = $type->properties();

            $commandTraverser = new NodeTraverser();

            /** @var ObjectType $type */
            foreach ($properties as $typeName => $type) {
                $commandTraverser->addVisitor(
                    new \OpenCodeModeling\CodeAst\NodeVisitor\Property(
                        new PropertyGenerator($typeName, $type->getType())
                    )
                );
            }
            $ast = $this->parser->parse($files[$name]['code']);

            $files[$name]['code'] = $this->printer->prettyPrintFile($commandTraverser->traverse($ast));
        }

        return $files;
    }

    public static function workflowComponentDescription(
        Parser $parser,
        PrettyPrinterAbstract $printer,
        string $inputAnalyzer,
        string $inputFiles,
        string $output
    ): \OpenCodeModeling\CodeGenerator\Workflow\Description {
        return new \OpenCodeModeling\CodeGenerator\Workflow\ComponentDescriptionWithSlot(
            new self(
                $parser,
                $printer
            ),
            $output,
            $inputAnalyzer,
            $inputFiles
        );
    }
}
