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

final class CommandFile
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
    private $filterCommandClassName;

    /**
     * @var callable
     **/
    private $filterCommandPath;

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
        callable $filterCommandClassName,
        ?callable $filterAggregatePath,
        ?callable $filterCommandPath
    ) {
        if ($filterAggregatePath && $filterCommandPath) {
            throw new RuntimeException(
                \sprintf('Providing $filterAggregatePath and $filterCommandPath is ambiguous. Choose only one!')
            );
        }

        $this->parser = $parser;
        $this->printer = $printer;
        $this->classInfoList = $classInfoList;
        $this->filterCommandClassName = $filterCommandClassName;
        $this->filterAggregatePath = $filterAggregatePath;
        $this->filterCommandPath = $filterCommandPath;
    }

    public function __invoke(
        EventSourcingAnalyzer $analyzer,
        string $commandPath
    ): array {
        $files = [];

        $classInfo = $this->classInfoList->classInfoForPath($commandPath);

        $namespaceUseVisitor = new NamespaceUse(
            'EventEngine\Data\ImmutableRecord',
            'EventEngine\Data\ImmutableRecordLogic'
        );

        foreach ($analyzer->commandMap() as $name => $command) {
            $className = ($this->filterCommandClassName)($command->label());
            $pathCommand = $commandPath;

            if ($this->filterAggregatePath !== null) {
                $aggregate = $analyzer->aggregateMap()->aggregateByCommand($command);

                if ($aggregate === null) {
                    throw new RuntimeException(
                        \sprintf('Command "%s" has no aggregate connection. Can not use aggregate name for path.', $name)
                    );
                }

                $pathCommand .= DIRECTORY_SEPARATOR . ($this->filterAggregatePath)($aggregate->label()) . DIRECTORY_SEPARATOR . 'Command';
            } elseif ($this->filterCommandPath !== null) {
                $pathCommand .= DIRECTORY_SEPARATOR . ($this->filterCommandPath)($command->label());
            }

            $commandFilename = $classInfo->getFilenameFromPathAndName($pathCommand, $className);

            $code = '';

            if (\file_exists($commandFilename) && \is_readable($commandFilename)) {
                $code = \file_get_contents($commandFilename);
            }

            $ast = $this->parser->parse($code);

            $commandClass = new ClassGenerator($className);
            $commandClass->setFinal(true);

            $commandTraverser = new NodeTraverser();
            $commandTraverser->addVisitor(new StrictType());
            $commandTraverser->addVisitor(new ClassNamespace($classInfo->getClassNamespaceFromPath($pathCommand)));
            $commandTraverser->addVisitor(new ClassFile($commandClass));
            $commandTraverser->addVisitor($namespaceUseVisitor);
            $commandTraverser->addVisitor(
                new ClassImplements(
                    'ImmutableRecord'
                )
            );
            $commandTraverser->addVisitor(
                new ClassUseTrait(
                    'ImmutableRecordLogic'
                )
            );

            $files[$name] = [
                'filename' => $commandFilename,
                'code' => $this->printer->prettyPrintFile($commandTraverser->traverse($ast)),
            ];
        }

        return $files;
    }
}
