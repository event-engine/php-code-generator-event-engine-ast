<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst;

use EventEngine\CodeGenerator\EventEngineAst\Code\AggregateBehaviourCommandMethod as Code;

use EventEngine\InspectioGraph\EventSourcingAnalyzer;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassMethod;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\PrettyPrinterAbstract;

final class AggregateBehaviourCommandMethod
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
    private $commandMethod;

    public function __construct(
        Parser $parser,
        PrettyPrinterAbstract $printer,
        Code $commandMethod
    ) {
        $this->parser = $parser;
        $this->printer = $printer;
        $this->commandMethod = $commandMethod;
    }

    /**
     * @param EventSourcingAnalyzer $analyzer
     * @param array $files
     * @return array Assoc array with aggregate name and file content
     */
    public function __invoke(
        EventSourcingAnalyzer $analyzer,
        array $files
    ): array {
        foreach ($analyzer->aggregateMap() as $aggregateDescription) {
            $aggregateVertex = $aggregateDescription->aggregate();
            $name = $aggregateVertex->name();

            if (! isset($files[$name])) {
                continue;
            }
            $ast = $this->parser->parse($files[$name]['code']);

            $aggregateTraverser = new NodeTraverser();

            $commandsToEventsMap = $aggregateDescription->commandsToEventsMap();

            /** @var \EventEngine\InspectioGraph\VertexType $commandVertex */
            foreach ($commandsToEventsMap as $commandVertex) {
                $aggregateTraverser->addVisitor(
                        new ClassMethod(
                            $this->commandMethod->generate(
                                $aggregateVertex,
                                $commandVertex, // @phpstan-ignore-line
                                ...$commandsToEventsMap[$commandVertex]
                            )
                        )
                    );
            }

            $files[$name]['code'] = $this->printer->prettyPrintFile($aggregateTraverser->traverse($ast));
        }

        return $files;
    }
}
