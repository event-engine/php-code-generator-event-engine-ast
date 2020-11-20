<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst;

use EventEngine\CodeGenerator\EventEngineAst\Code\ClassConstant as CodeClassConstant;
use EventEngine\CodeGenerator\EventEngineAst\Code\EventDescription as CodeEventDescription;
use EventEngine\CodeGenerator\EventEngineAst\NodeVisitor\ClassMethodDescribeEvent;
use EventEngine\InspectioGraph\EventSourcingAnalyzer;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassConstant;
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
}
