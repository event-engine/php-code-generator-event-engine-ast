<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine;

use EventEngine\InspectioGraph\EventSourcingAnalyzer;
use OpenCodeModeling\CodeAst\Code\BodyGenerator;
use OpenCodeModeling\CodeAst\Code\MethodGenerator;
use OpenCodeModeling\CodeAst\Code\ParameterGenerator;
use OpenCodeModeling\CodeAst\Code\PropertyGenerator;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassMethod;
use OpenCodeModeling\CodeAst\NodeVisitor\Property;
use OpenCodeModeling\CodeAst\NodeVisitor\StrictType;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\PrettyPrinterAbstract;

final class AggregateStateImmutableRecordOverride
{
    /**
     * @var Parser
     **/
    private $parser;

    /**
     * @var PrettyPrinterAbstract
     */
    private $printer;

    public function __construct(
        Parser $parser,
        PrettyPrinterAbstract $printer
    ) {
        $this->parser = $parser;
        $this->printer = $printer;
    }

    /**
     * @param EventSourcingAnalyzer $analyzer
     * @param array $files
     * @return array Assoc array with aggregate name and file content
     */
    public function __invoke(EventSourcingAnalyzer $analyzer, array $files): array
    {
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new StrictType());

        $fromRecordDataMethod = new ClassMethod($this->fromRecordDataMethod());
        $fromArrayMethod = new ClassMethod($this->fromArrayMethod());
        $withMethod = new ClassMethod($this->withMethod());
        $toArrayMethod = new ClassMethod($this->toArrayMethod());
        $equalsMethod = new ClassMethod($this->equalsMethod());
        $constructorMethod = new ClassMethod($this->constructorMethod());
        $setRecordDataMethod = new ClassMethod($this->setRecordDataMethod());
        $stateProperty = new Property($this->stateProperty());

        foreach ($analyzer->aggregateMap()->aggregateVertexMap() as $name => $vertex) {
            if (! isset($files[$name])) {
                continue;
            }
            $ast = $this->parser->parse($files[$name]['code']);

            $aggregateTraverser = new NodeTraverser();

            $aggregateTraverser->addVisitor($stateProperty);
            $aggregateTraverser->addVisitor($fromRecordDataMethod);
            $aggregateTraverser->addVisitor($fromArrayMethod);
            $aggregateTraverser->addVisitor($withMethod);
            $aggregateTraverser->addVisitor($toArrayMethod);
            $aggregateTraverser->addVisitor($equalsMethod);
            $aggregateTraverser->addVisitor($constructorMethod);
            $aggregateTraverser->addVisitor($setRecordDataMethod);

            $files[$name]['code'] = $this->printer->prettyPrintFile($aggregateTraverser->traverse($ast));
        }

        return $files;
    }

    public static function workflowComponentDescription(
        Parser $parser,
        PrettyPrinterAbstract $printer,
        string $inputAnalyzer,
        string $inputFiles,
        string $output
    ): \OpenCodeModeling\CodeGenerator\Workflow\Description {
        $instance = new self(
            $parser,
            $printer
        );

        return new \OpenCodeModeling\CodeGenerator\Workflow\ComponentDescriptionWithSlot(
            $instance,
            $output,
            $inputAnalyzer,
            $inputFiles
        );
    }

    private function stateProperty(): PropertyGenerator
    {
        return new PropertyGenerator(
            'state',
            []
        );
    }

    private function fromRecordDataMethod(): MethodGenerator
    {
        $method = new MethodGenerator(
            'fromRecordData',
            [new ParameterGenerator('recordData', 'array')],
            MethodGenerator::FLAG_PUBLIC | MethodGenerator::FLAG_STATIC,
            new BodyGenerator(
                $this->parser,
                'return new self($recordData);'
            )
        );

        return $method;
    }

    private function fromArrayMethod(): MethodGenerator
    {
        $method = new MethodGenerator(
            'fromArray',
            [new ParameterGenerator('nativeData', 'array')],
            MethodGenerator::FLAG_PUBLIC | MethodGenerator::FLAG_STATIC,
            new BodyGenerator(
                $this->parser,
                'return new self(null, $nativeData);'
            )
        );

        return $method;
    }

    private function withMethod(): MethodGenerator
    {
        $method = new MethodGenerator(
            'with',
            [new ParameterGenerator('recordData', 'array')],
            MethodGenerator::FLAG_PUBLIC,
            new BodyGenerator(
                $this->parser,
                '$copy = clone $this; $copy->setRecordData($recordData); return $copy;'
            )
        );

        return $method;
    }

    private function toArrayMethod(): MethodGenerator
    {
        $method = new MethodGenerator(
            'toArray',
            [],
            MethodGenerator::FLAG_PUBLIC,
            new BodyGenerator(
                $this->parser,
                'return $this->state;'
            )
        );
        $method->setReturnType('array');

        return $method;
    }

    private function equalsMethod(): MethodGenerator
    {
        $method = new MethodGenerator(
            'equals',
            [new ParameterGenerator('other', 'ImmutableRecord')],
            MethodGenerator::FLAG_PUBLIC,
            new BodyGenerator(
                $this->parser,
                'return $this->state === $other->toArray();'
            )
        );
        $method->setReturnType('bool');

        return $method;
    }

    private function constructorMethod(): MethodGenerator
    {
        $method = new MethodGenerator(
            '__construct',
            [
                (new ParameterGenerator('recordData', 'array'))->setDefaultValue(null),
                (new ParameterGenerator('nativeData', 'array'))->setDefaultValue(null),
            ],
            MethodGenerator::FLAG_PRIVATE,
            new BodyGenerator(
                $this->parser,
                <<<'CODE'
        if ($recordData) {
            $this->setRecordData($recordData);
        }
        if ($nativeData) {
            $this->state = array_merge($this->state, $nativeData);
        }
CODE
            )
        );

        return $method;
    }

    private function setRecordDataMethod(): MethodGenerator
    {
        $method = new MethodGenerator(
            'setRecordData',
            [new ParameterGenerator('recordData', 'array')],
            MethodGenerator::FLAG_PRIVATE,
            new BodyGenerator(
                $this->parser,
                '$this->state = array_merge($this->state, $recordData);'
            )
        );
        $method->setReturnType('void');

        return $method;
    }
}
