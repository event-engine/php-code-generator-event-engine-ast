<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst;

use EventEngine\InspectioGraph\EventSourcingAnalyzer;
use EventEngine\InspectioGraph\VertexType;
use OpenCodeModeling\CodeAst\Code\ClassGenerator;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassFile;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassImplements;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassNamespace;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassUseTrait;
use OpenCodeModeling\CodeAst\NodeVisitor\NamespaceUse;
use OpenCodeModeling\CodeAst\NodeVisitor\StrictType;
use OpenCodeModeling\CodeAst\Package\ClassInfoList;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\PrettyPrinterAbstract;

final class AggregateStateFile
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
    private $filterClassName;

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
        callable $filterClassName,
        ?callable $filterAggregateStatePath
    ) {
        $this->parser = $parser;
        $this->printer = $printer;
        $this->classInfoList = $classInfoList;
        $this->filterClassName = $filterClassName;
        $this->filterAggregateStatePath = $filterAggregateStatePath;
    }

    /**
     * @param \EventEngine\InspectioGraph\EventSourcingAnalyzer $analyzer
     * @param string $path
     * @return array Assoc array with aggregate name and file content
     */
    public function __invoke(EventSourcingAnalyzer $analyzer, string $path): array
    {
        $files = [];

        $classInfo = $this->classInfoList->classInfoForPath($path);

        $namespaceUseVisitor = new NamespaceUse(
            'EventEngine\Data\ImmutableRecord',
            'EventEngine\Data\ImmutableRecordLogic'
        );

        /** @var VertexType $vertex */
        foreach ($analyzer->aggregateMap()->aggregateVertexMap() as $name => $vertex) {
            $className = ($this->filterClassName)($vertex->label());

            $pathState = $path;

            if ($this->filterAggregateStatePath !== null) {
                $pathState .= DIRECTORY_SEPARATOR . ($this->filterAggregateStatePath)($vertex->label());
            }

            $filename = $classInfo->getFilenameFromPathAndName($pathState, $className);

            $code = '';

            if (\file_exists($filename) && \is_readable($filename)) {
                $code = \file_get_contents($filename);
            }
            $ast = $this->parser->parse($code);
            $aggregateClass = new ClassGenerator($className);
            $aggregateClass->setFinal(true);

            // order is important
            $aggregateTraverser = new NodeTraverser();
            $aggregateTraverser->addVisitor(new StrictType());
            $aggregateTraverser->addVisitor(new ClassNamespace($classInfo->getClassNamespaceFromPath($pathState)));
            $aggregateTraverser->addVisitor($namespaceUseVisitor);
            $aggregateTraverser->addVisitor(new ClassFile($aggregateClass));
            $aggregateTraverser->addVisitor(new ClassImplements('ImmutableRecord'));
            $aggregateTraverser->addVisitor(new ClassUseTrait('ImmutableRecordLogic'));

            $files[$name . AggregateStateFactory::STATE_SUFFIX] = [
                'filename' => $filename,
                'code' => $this->printer->prettyPrintFile($aggregateTraverser->traverse($ast)),
            ];
        }

        return $files;
    }
}
