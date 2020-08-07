<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine;

use EventEngine\InspectioGraph\EventSourcingAnalyzer;
use OpenCodeModeling\CodeAst\Code\ClassGenerator;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassFile;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassNamespace;
use OpenCodeModeling\CodeAst\NodeVisitor\NamespaceUse;
use OpenCodeModeling\CodeAst\NodeVisitor\StrictType;
use OpenCodeModeling\CodeGenerator\Code\ClassInfoList;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\PrettyPrinterAbstract;

class AggregateBehaviourFile
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
    private $filterAggregateClassName;

    /**
     * @var callable
     **/
    private $filterAggregateStateClassName;

    /**
     * @var callable
     **/
    private $filterAggregatePath;

    /**
     * @var callable
     **/
    private $filterAggregateStatePath;

    /**
     * @var ClassInfoList
     **/
    private $classInfoList;

    public function __construct(
        Parser $parser,
        PrettyPrinterAbstract $printer,
        ClassInfoList $classInfoList,
        callable $filterAggregateClassName,
        callable $filterAggregateStateClassName,
        ?callable $filterAggregatePath,
        ?callable $filterAggregateStatePath
    ) {
        $this->parser = $parser;
        $this->printer = $printer;
        $this->classInfoList = $classInfoList;
        $this->filterAggregateClassName = $filterAggregateClassName;
        $this->filterAggregateStateClassName = $filterAggregateStateClassName;
        $this->filterAggregatePath = $filterAggregatePath;
        $this->filterAggregateStatePath = $filterAggregateStatePath;
    }

    public function __invoke(
        EventSourcingAnalyzer $analyzer,
        string $aggregatePath,
        string $aggregateStatePath,
        string $apiEventFilename
    ): array {
        $files = [];

        $classInfo = $this->classInfoList->classInfoForPath($aggregatePath);
        $classInfoEvent = $this->classInfoList->classInfoForPath($apiEventFilename);

        $namespaceUseVisitor = new NamespaceUse(
            $classInfoEvent->getFullyQualifiedClassNameFromFilename($apiEventFilename),
            'EventEngine\Messaging\Message',
            'Generator'
        );

        foreach ($analyzer->aggregateMap()->aggregateVertexMap() as $name => $vertex) {
            $className = ($this->filterAggregateClassName)($vertex->label());
            $aggregateStateClassName = ($this->filterAggregateStateClassName)($vertex->label());
            $pathAggregate = $aggregatePath;
            $pathAggregateState = $aggregateStatePath;

            if ($this->filterAggregatePath !== null) {
                $pathAggregate .= DIRECTORY_SEPARATOR . ($this->filterAggregatePath)($vertex->label());
            }
            if ($this->filterAggregateStatePath !== null) {
                $pathAggregateState .= DIRECTORY_SEPARATOR . ($this->filterAggregateStatePath)($vertex->label());
            }

            $aggregateFilename = $classInfo->getFilenameFromPathAndName($pathAggregate, $className);
            $aggergateStateFilename = $classInfo->getFilenameFromPathAndName($pathAggregateState, $aggregateStateClassName);

            $code = '';

            if (\file_exists($aggregateFilename) && \is_readable($aggregateFilename)) {
                $code = \file_get_contents($aggregateFilename);
            }

            $ast = $this->parser->parse($code);

            $aggregateClass = new ClassGenerator($className);
            $aggregateClass->setFinal(true);

            $aggregateTraverser = new NodeTraverser();
            $aggregateTraverser->addVisitor(new StrictType());
            $aggregateTraverser->addVisitor(new ClassNamespace($classInfo->getClassNamespaceFromPath($pathAggregate)));
            $aggregateTraverser->addVisitor($namespaceUseVisitor);
            $aggregateTraverser->addVisitor(
                new NamespaceUse(
                    [$classInfoEvent->getFullyQualifiedClassNameFromFilename($aggergateStateFilename), 'State']
                )
            );
            $aggregateTraverser->addVisitor(new ClassFile($aggregateClass));

            $files[$name] = [
                'filename' => $aggregateFilename,
                'code' => $this->printer->prettyPrintFile($aggregateTraverser->traverse($ast)),
            ];
        }

        return $files;
    }

    public static function workflowComponentDescription(
        Parser $parser,
        PrettyPrinterAbstract $printer,
        ClassInfoList $classInfoList,
        callable $filterAggregateClassName,
        callable $filterAggregateStateClassName,
        ?callable $filterAggregatePath,
        ?callable $filterAggregateStatePath,
        string $inputAnalyzer,
        string $inputAggregatePath,
        string $inputAggregateStatePath,
        string $inputApiEventFilename,
        string $output
    ): \OpenCodeModeling\CodeGenerator\Workflow\Description {
        return new \OpenCodeModeling\CodeGenerator\Workflow\ComponentDescriptionWithSlot(
            new self(
                $parser,
                $printer,
                $classInfoList,
                $filterAggregateClassName,
                $filterAggregateStateClassName,
                $filterAggregatePath,
                $filterAggregateStatePath
            ),
            $output,
            $inputAnalyzer,
            $inputAggregatePath,
            $inputAggregateStatePath,
            $inputApiEventFilename
        );
    }
}
