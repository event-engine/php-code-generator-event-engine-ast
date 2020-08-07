<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine;

use EventEngine\CodeGenerator\Cartridge\EventEngine\Code\DescriptionFileMethod as CodeDescriptionFileMethod;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassImplements;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassMethod;
use OpenCodeModeling\CodeAst\NodeVisitor\NamespaceUse;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\PrettyPrinterAbstract;

class DescriptionFileMethod
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
                'EventEngine\JsonSchema\JsonSchema'
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

    public static function workflowComponentDescription(
        Parser $parser,
        PrettyPrinterAbstract $printer,
        string $inputCode,
        string $output
    ): \OpenCodeModeling\CodeGenerator\Workflow\Description {
        return new \OpenCodeModeling\CodeGenerator\Workflow\ComponentDescriptionWithSlot(
            new self($parser, $printer),
            $output,
            $inputCode
        );
    }
}
