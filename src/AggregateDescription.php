<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst;

use EventEngine\CodeGenerator\EventEngineAst\Code\AggregateDescription as CodeAggregateDescription;
use EventEngine\CodeGenerator\EventEngineAst\Code\ClassConstant as CodeClassConstant;
use EventEngine\CodeGenerator\EventEngineAst\NodeVisitor\ClassMethodDescribeAggregate;
use EventEngine\InspectioGraph\CommandType;
use EventEngine\InspectioGraph\EventSourcingAnalyzer;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassConstant;
use OpenCodeModeling\CodeAst\NodeVisitor\NamespaceUse;
use OpenCodeModeling\CodeAst\Package\ClassInfoList;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\PrettyPrinterAbstract;

final class AggregateDescription
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
     * @var ClassInfoList
     **/
    private $classInfoList;

    /**
     * @var callable
     **/
    private $filterAggregateClassName;

    /**
     * @var callable
     **/
    private $filterAggregatePath;

    /**
     * @var callable
     **/
    private $filterStoreStateIn;

    /**
     * @var CodeAggregateDescription
     **/
    private $aggregateDescription;

    public function __construct(
        Parser $parser,
        PrettyPrinterAbstract $printer,
        ClassInfoList $classInfoList,
        CodeAggregateDescription $aggregateDescription,
        CodeClassConstant $classConstant,
        callable $filterAggregateClassName,
        ?callable $filterAggregatePath,
        ?callable $filterStoreStateIn
    ) {
        $this->parser = $parser;
        $this->printer = $printer;
        $this->classInfoList = $classInfoList;
        $this->aggregateDescription = $aggregateDescription;
        $this->classConstant = $classConstant;
        $this->filterAggregateClassName = $filterAggregateClassName;
        $this->filterAggregatePath = $filterAggregatePath;
        $this->filterStoreStateIn = $filterStoreStateIn;
    }

    public function __invoke(EventSourcingAnalyzer $analyzer, string $code, string $aggregatePath): string
    {
        $classInfo = $this->classInfoList->classInfoForPath($aggregatePath);
        $ast = $this->parser->parse($code);
        $namespaceUses = [];
        $visitors = [];

        foreach ($analyzer->aggregateMap() as $aggregateDescription) {
            $aggregateVertex = $aggregateDescription->aggregate();

            $aggregateBehaviourClassName = ($this->filterAggregateClassName)($aggregateVertex->label());

            $storeStateIn = null;
            $pathAggregate = $aggregatePath;

            if ($this->filterAggregatePath !== null) {
                $pathAggregate .= DIRECTORY_SEPARATOR . ($this->filterAggregatePath)($aggregateVertex->label());
            }
            if ($this->filterStoreStateIn !== null) {
                $storeStateIn = ($this->filterStoreStateIn)($aggregateVertex->label());
            }

            $filename = $classInfo->getFilenameFromPathAndName($pathAggregate, $aggregateBehaviourClassName);
            $namespaceUses[] = $classInfo->getFullyQualifiedClassNameFromFilename($filename);

            $commandsToEventsMap = $aggregateDescription->commandsToEventsMap();

            /** @var CommandType $commandVertex */
            foreach ($commandsToEventsMap as $commandVertex) {
                $visitors[] = new ClassMethodDescribeAggregate(
                    $this->aggregateDescription->generate(
                        $aggregateBehaviourClassName,
                        $aggregateBehaviourClassName,
                        $storeStateIn,
                        $commandVertex,
                        $aggregateVertex,
                        ...$commandsToEventsMap[$commandVertex]
                    )
                );
            }
        }

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NamespaceUse(...$namespaceUses));

        foreach ($analyzer->aggregateMap()->aggregateVertexMap() as $aggregateVertex) {
            $traverser->addVisitor(
                new ClassConstant($this->classConstant->generate($aggregateVertex))
            );
        }

        foreach ($visitors as $visitor) {
            $traverser->addVisitor($visitor);
        }

        return $this->printer->prettyPrintFile($traverser->traverse($ast));
    }
}
