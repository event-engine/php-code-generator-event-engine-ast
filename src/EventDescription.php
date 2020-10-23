<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine;

use EventEngine\CodeGenerator\Cartridge\EventEngine\Code\ClassConstant as CodeClassConstant;
use EventEngine\CodeGenerator\Cartridge\EventEngine\Code\EventDescription as CodeEventDescription;
use EventEngine\CodeGenerator\Cartridge\EventEngine\NodeVisitor\ClassMethodDescribeEvent;
use EventEngine\InspectioGraph\EventSourcingAnalyzer;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassConstant;
use OpenCodeModeling\CodeGenerator\Workflow\ComponentDescriptionWithSlot;
use OpenCodeModeling\CodeGenerator\Workflow\Description;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\PrettyPrinterAbstract;

final class EventDescription
{
    /**
     * @var Parser
     **/
    private $parser;

    /**
     * @var PrettyPrinterAbstract
     */
    private $printer;

    /**
     * @var CodeClassConstant
     **/
    private $classConstant;

    /**
     * @var CodeEventDescription
     **/
    private $eventDescription;

    public function __construct(
        Parser $parser,
        PrettyPrinterAbstract $printer,
        CodeEventDescription $eventDescription,
        CodeClassConstant $classConstant
    ) {
        $this->parser = $parser;
        $this->printer = $printer;
        $this->eventDescription = $eventDescription;
        $this->classConstant = $classConstant;
    }

    public function __invoke(EventSourcingAnalyzer $analyzer, string $code, ?array $inputSchemaMetadata = null): string
    {
        $ast = $this->parser->parse($code);

        $traverser = new NodeTraverser();

        foreach ($analyzer->eventMap() as $name => $eventVertex) {
            $traverser->addVisitor(
                new ClassConstant($this->classConstant->generate($eventVertex))
            );
            $traverser->addVisitor(
                new ClassMethodDescribeEvent(
                    $this->eventDescription->generate(
                        $eventVertex,
                        $inputSchemaMetadata[$name]['filename'] ?? null
                    )
                )
            );
        }

        return $this->printer->prettyPrintFile($traverser->traverse($ast));
    }

    public static function workflowComponentDescription(
        Parser $parser,
        PrettyPrinterAbstract $printer,
        CodeEventDescription $eventDescription,
        CodeClassConstant $classConstant,
        string $inputAnalyzer,
        string $inputCode,
        string $inputSchemaMetadata,
        string $output
    ): Description {
        $instance = new self(
            $parser,
            $printer,
            $eventDescription,
            $classConstant
        );

        return new ComponentDescriptionWithSlot(
            $instance,
            $output,
            $inputAnalyzer,
            $inputCode,
            $inputSchemaMetadata
        );
    }
}
