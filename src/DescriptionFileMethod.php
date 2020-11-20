<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst;

use EventEngine\CodeGenerator\EventEngineAst\Code\DescriptionFileMethod as CodeDescriptionFileMethod;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassImplements;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassMethod;
use OpenCodeModeling\CodeAst\NodeVisitor\NamespaceUse;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\PrettyPrinterAbstract;

final class DescriptionFileMethod
{
    /**
     * @var Parser
     **/
    private $parser;

    /**
     * @var PrettyPrinterAbstract
     */
    private $printer;

    public function __construct(Parser $parser, PrettyPrinterAbstract $printer)
    {
        $this->parser = $parser;
        $this->printer = $printer;
    }

    public function __invoke(string $code): string
    {
        $ast = $this->parser->parse($code);

        $traverser = new NodeTraverser();
        $traverser->addVisitor(
            new NamespaceUse(
                'EventEngine\EventEngine',
                'EventEngine\EventEngineDescription',
                'EventEngine\JsonSchema\JsonSchema',
                'EventEngine\JsonSchema\JsonSchemaArray'
            )
        );
        $traverser->addVisitor(
            new ClassImplements(
                'EventEngineDescription'
            )
        );
        $traverser->addVisitor(new ClassMethod(CodeDescriptionFileMethod::generate()));

        return $this->printer->prettyPrintFile($traverser->traverse($ast));
    }
}
