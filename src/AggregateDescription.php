<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine;

use EventEngine\CodeGenerator\Cartridge\EventEngine\Code\AggregateDescription as CodeAggregateDescription;
use EventEngine\CodeGenerator\Cartridge\EventEngine\Code\ClassConstant as CodeClassConstant;
use EventEngine\CodeGenerator\Cartridge\EventEngine\NodeVisitor\ClassConstant;
use EventEngine\CodeGenerator\Cartridge\EventEngine\NodeVisitor\ClassMethodDescribeAggregate;
use EventEngine\InspectioGraph\Command;
use EventEngine\InspectioGraph\EventSourcingAnalyzer;
use OpenCodeModeling\CodeAst\NodeVisitor\NamespaceUse;
use OpenCodeModeling\CodeGenerator\Code\ClassInfoList;
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
        ?callable $filterAggregatePath
    ) {
        $this->parser = $parser;
        $this->printer = $printer;
        $this->classInfoList = $classInfoList;
        $this->aggregateDescription = $aggregateDescription;
        $this->classConstant = $classConstant;
        $this->filterAggregateClassName = $filterAggregateClassName;
        $this->filterAggregatePath = $filterAggregatePath;
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

            $pathAggregate = $aggregatePath;

            if ($this->filterAggregatePath !== null) {
                $pathAggregate .= DIRECTORY_SEPARATOR . ($this->filterAggregatePath)($aggregateVertex->label());
            }

            $filename = $classInfo->getFilenameFromPathAndName($pathAggregate, $aggregateBehaviourClassName);
            $namespaceUses[] = $classInfo->getFullyQualifiedClassNameFromFilename($filename);

            $commandsToEventsMap = $aggregateDescription->commandsToEventsMap();

            /** @var Command $commandVertex */
            foreach ($commandsToEventsMap as $commandVertex) {
                $visitors[] = new ClassMethodDescribeAggregate(
                    $this->aggregateDescription->generate(
                        $aggregateBehaviourClassName,
                        $aggregateBehaviourClassName,
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

    public static function workflowComponentDescription(
        Parser $parser,
        PrettyPrinterAbstract $printer,
        ClassInfoList $classInfoList,
        CodeAggregateDescription $aggregateDescription,
        CodeClassConstant $classConstant,
        callable $filterAggregateClassName,
        ?callable $filterAggregatePath,
        string $inputAnalyzer,
        string $inputCode,
        string $inputAggregatePath,
        string $output
    ): \OpenCodeModeling\CodeGenerator\Workflow\Description {
        $instance = new self(
            $parser,
            $printer,
            $classInfoList,
            $aggregateDescription,
            $classConstant,
            $filterAggregateClassName,
            $filterAggregatePath
        );

        return new \OpenCodeModeling\CodeGenerator\Workflow\ComponentDescriptionWithSlot(
            $instance,
            $output,
            $inputAnalyzer,
            $inputCode,
            $inputAggregatePath
        );
    }
}
