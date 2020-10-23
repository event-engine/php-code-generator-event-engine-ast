<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine;

use EventEngine\CodeGenerator\Cartridge\EventEngine\Code\ClassConstant as CodeClassConstant;
use EventEngine\CodeGenerator\Cartridge\EventEngine\Code\CommandDescription as CodeCommandDescription;
use EventEngine\CodeGenerator\Cartridge\EventEngine\NodeVisitor\ClassMethodDescribeCommand;
use EventEngine\InspectioGraph\EventSourcingAnalyzer;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassConstant;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\PrettyPrinterAbstract;

final class CommandDescription
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
     * @var CodeCommandDescription
     **/
    private $commandDescription;

    public function __construct(
        Parser $parser,
        PrettyPrinterAbstract $printer,
        CodeCommandDescription $commandDescription,
        CodeClassConstant $classConstant
    ) {
        $this->parser = $parser;
        $this->printer = $printer;
        $this->commandDescription = $commandDescription;
        $this->classConstant = $classConstant;
    }

    public function __invoke(EventSourcingAnalyzer $analyzer, string $code, ?array $inputSchemaMetadata = null): string
    {
        $ast = $this->parser->parse($code);

        $traverser = new NodeTraverser();

        foreach ($analyzer->commandMap() as $name => $commandVertex) {
            $traverser->addVisitor(
                new ClassConstant($this->classConstant->generate($commandVertex))
            );
            $traverser->addVisitor(
                new ClassMethodDescribeCommand(
                    $this->commandDescription->generate(
                        $commandVertex,
                        $inputSchemaMetadata[$name]['filename'] ?? null
                    )
                )
            );
        }

        return $this->printer->prettyPrintFile($traverser->traverse($ast));
    }

    public static function workflowComponentDescription(
        Parser $parser,
        PrettyPrinterAbstract $printer,
        CodeCommandDescription $commandDescription,
        CodeClassConstant $classConstant,
        string $inputAnalyzer,
        string $inputCode,
        string $inputSchemaMetadata,
        string $output
    ): \OpenCodeModeling\CodeGenerator\Workflow\Description {
        $instance = new self(
            $parser,
            $printer,
            $commandDescription,
            $classConstant
        );

        return new \OpenCodeModeling\CodeGenerator\Workflow\ComponentDescriptionWithSlot(
            $instance,
            $output,
            $inputAnalyzer,
            $inputCode,
            $inputSchemaMetadata
        );
    }
}
