<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst;

use EventEngine\CodeGenerator\EventEngineAst\Code\AggregateStateMethod as CodeAggregateState;
use EventEngine\InspectioGraph\EventSourcingAnalyzer;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassMethod;
use OpenCodeModeling\CodeAst\NodeVisitor\StrictType;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\PrettyPrinterAbstract;

final class AggregateStateModifyMethod
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
     * @var callable
     **/
    private $filterMethodName;

    public function __construct(
        Parser $parser,
        PrettyPrinterAbstract $printer,
        callable $filterMethodName
    ) {
        $this->parser = $parser;
        $this->printer = $printer;
        $this->filterMethodName = $filterMethodName;
    }

    /**
     * @param EventSourcingAnalyzer $analyzer
     * @param array $files
     * @return array Assoc array with aggregate name and file content
     */
    public function __invoke(EventSourcingAnalyzer $analyzer, array $files): array
    {
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new StrictType());

        $withMethod = new CodeAggregateState($this->parser, $this->filterMethodName);

        foreach ($analyzer->aggregateMap() as $aggregateDescription) {
            $aggregateVertex = $aggregateDescription->aggregate();
            $name = $aggregateVertex->name() . AggregateStateFactory::STATE_SUFFIX;

            if (! isset($files[$name])) {
                continue;
            }
            $ast = $this->parser->parse($files[$name]['code']);

            $aggregateTraverser = new NodeTraverser();

            $commandsToEventsMap = $aggregateDescription->commandsToEventsMap();

            foreach ($commandsToEventsMap as $commandVertex) {
                foreach ($commandsToEventsMap[$commandVertex] as $eventVertex) {
                    $aggregateTraverser->addVisitor(
                        new ClassMethod(
                            $withMethod->generate(
                                $eventVertex
                            )
                        )
                    );
                }
            }

            $files[$name]['code'] = $this->printer->prettyPrintFile($aggregateTraverser->traverse($ast));
        }

        return $files;
    }
}
