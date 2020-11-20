<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst;

use EventEngine\CodeGenerator\EventEngineAst\Exception\RuntimeException;
use EventEngine\InspectioGraph\EventSourcingAnalyzer;
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

final class EventFile
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
    private $filterEventClassName;

    /**
     * @var callable
     **/
    private $filterEventPath;

    /**
     * @var callable
     **/
    private $filterAggregatePath;

    /**
     * @var ClassInfoList
     **/
    private $classInfoList;

    public function __construct(
        Parser $parser,
        PrettyPrinterAbstract $printer,
        ClassInfoList $classInfoList,
        callable $filterEventClassName,
        ?callable $filterAggregatePath,
        ?callable $filterEventPath
    ) {
        if ($filterAggregatePath && $filterEventPath) {
            throw new RuntimeException(
                \sprintf('Providing $filterAggregatePath and $filterEventPath is ambiguous. Choose only one!')
            );
        }

        $this->parser = $parser;
        $this->printer = $printer;
        $this->classInfoList = $classInfoList;
        $this->filterEventClassName = $filterEventClassName;
        $this->filterAggregatePath = $filterAggregatePath;
        $this->filterEventPath = $filterEventPath;
    }

    public function __invoke(
        EventSourcingAnalyzer $analyzer,
        string $eventPath
    ): array {
        $files = [];

        $classInfo = $this->classInfoList->classInfoForPath($eventPath);

        $namespaceUseVisitor = new NamespaceUse(
            'EventEngine\Data\ImmutableRecord',
            'EventEngine\Data\ImmutableRecordLogic'
        );

        foreach ($analyzer->eventMap() as $name => $event) {
            $className = ($this->filterEventClassName)($event->label());
            $pathEvent = $eventPath;

            if ($this->filterAggregatePath !== null) {
                $aggregate = $analyzer->aggregateMap()->aggregateByEvent($event);

                if ($aggregate === null) {
                    throw new RuntimeException(
                        \sprintf('Event "%s" has no aggregate connection. Can not use aggregate name for path.', $name)
                    );
                }

                $pathEvent .= DIRECTORY_SEPARATOR . ($this->filterAggregatePath)($aggregate->label()) . DIRECTORY_SEPARATOR . 'Event';
            } elseif ($this->filterEventPath !== null) {
                $pathEvent .= DIRECTORY_SEPARATOR . ($this->filterEventPath)($event->label());
            }

            $eventFilename = $classInfo->getFilenameFromPathAndName($pathEvent, $className);

            $code = '';

            if (\file_exists($eventFilename) && \is_readable($eventFilename)) {
                $code = \file_get_contents($eventFilename);
            }

            $ast = $this->parser->parse($code);

            $eventClass = new ClassGenerator($className);
            $eventClass->setFinal(true);

            $eventTraverser = new NodeTraverser();
            $eventTraverser->addVisitor(new StrictType());
            $eventTraverser->addVisitor(new ClassNamespace($classInfo->getClassNamespaceFromPath($pathEvent)));
            $eventTraverser->addVisitor(new ClassFile($eventClass));
            $eventTraverser->addVisitor($namespaceUseVisitor);
            $eventTraverser->addVisitor(
                new ClassImplements(
                    'ImmutableRecord'
                )
            );
            $eventTraverser->addVisitor(
                new ClassUseTrait(
                    'ImmutableRecordLogic'
                )
            );

            $files[$name] = [
                'filename' => $eventFilename,
                'code' => $this->printer->prettyPrintFile($eventTraverser->traverse($ast)),
            ];
        }

        return $files;
    }
}
