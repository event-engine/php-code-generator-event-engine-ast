<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine;

use EventEngine\CodeGenerator\Cartridge\EventEngine\Code\AggregateBehaviourEventMethod as Code;

use EventEngine\InspectioGraph\EventSourcingAnalyzer;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassMethod;

use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\PrettyPrinterAbstract;

final class AggregateBehaviourEventMethod
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
     * @var Code
     **/
    private $eventMethod;

    public function __construct(
        Parser $parser,
        PrettyPrinterAbstract $printer,
        Code $eventMethod
    ) {
        $this->parser = $parser;
        $this->printer = $printer;
        $this->eventMethod = $eventMethod;
    }

    /**
     * @param EventSourcingAnalyzer $analyzer
     * @param array $files
     * @return array Assoc array with aggregate name and file content
     */
    public function __invoke(EventSourcingAnalyzer $analyzer, array $files): array
    {
        foreach ($analyzer->aggregateMap() as $aggregateDescription) {
            $aggregateVertex = $aggregateDescription->aggregate();
            $name = $aggregateVertex->name();

            if (! isset($files[$name])) {
                continue;
            }
            $ast = $this->parser->parse($files[$name]['code']);

            $aggregateTraverser = new NodeTraverser();

            $commandsToEventsMap = $aggregateDescription->commandsToEventsMap();

            /** @var \EventEngine\InspectioGraph\Vertex $commandVertex */
            foreach ($commandsToEventsMap as $commandVertex) {
                foreach ($commandsToEventsMap[$commandVertex] as $eventVertex) {
                    $aggregateTraverser->addVisitor(
                        new ClassMethod(
                            // @phpstan-ignore-next-line
                            $this->eventMethod->generate($aggregateVertex, $commandVertex, $eventVertex)
                        )
                    );
                }
            }

            $files[$name]['code'] = $this->printer->prettyPrintFile($aggregateTraverser->traverse($ast));
        }

        return $files;
    }

    public static function workflowComponentDescription(
        Parser $parser,
        PrettyPrinterAbstract $printer,
        Code $eventMethod,
        string $inputAnalyzer,
        string $inputFiles,
        string $output
    ): \OpenCodeModeling\CodeGenerator\Workflow\Description {
        $instance = new self(
            $parser,
            $printer,
            $eventMethod
        );

        return new \OpenCodeModeling\CodeGenerator\Workflow\ComponentDescriptionWithSlot(
            $instance,
            $output,
            $inputAnalyzer,
            $inputFiles
        );
    }
}
