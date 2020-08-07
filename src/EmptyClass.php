<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine;

use OpenCodeModeling\CodeAst\Code\ClassGenerator;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassFile;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassNamespace;
use OpenCodeModeling\CodeAst\NodeVisitor\StrictType;
use OpenCodeModeling\CodeGenerator\Code\ClassInfoList;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\PrettyPrinterAbstract;

/**
 * Creates an empty PHP class if file does not exists. If it exists, it will be loaded.
 */
final class EmptyClass
{
    /**
     * @var ClassInfoList
     **/
    private $classInfoList;

    /**
     * @var Parser
     **/
    private $parser;

    /**
     * @var PrettyPrinterAbstract
     */
    private $printer;

    public function __construct(ClassInfoList $classInfoList, Parser $parser, PrettyPrinterAbstract $printer)
    {
        $this->classInfoList = $classInfoList;
        $this->parser = $parser;
        $this->printer = $printer;
    }

    public function __invoke(string $filename): string
    {
        $classInfo = $this->classInfoList->classInfoForFilename($filename);

        [$path, $name] = $classInfo->getPathAndNameFromFilename($filename);

        $classGenerator = new ClassGenerator($name);
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new StrictType());
        $traverser->addVisitor(new ClassNamespace($classInfo->getClassNamespaceFromPath($path)));
        $traverser->addVisitor(new ClassFile($classGenerator));

        $code = '';

        if (\file_exists($filename) && \is_readable($filename)) {
            $code = \file_get_contents($filename);
        }
        $ast = $this->parser->parse($code);

        return $this->printer->prettyPrintFile($traverser->traverse($ast));
    }

    public static function workflowComponentDescription(
        ClassInfoList $classInfoList,
        Parser $parser,
        PrettyPrinterAbstract $printer,
        string $inputFilename,
        string $output
    ): \OpenCodeModeling\CodeGenerator\Workflow\Description {
        return new \OpenCodeModeling\CodeGenerator\Workflow\ComponentDescriptionWithSlot(
            new self($classInfoList, $parser, $printer),
            $output,
            $inputFilename
        );
    }
}
