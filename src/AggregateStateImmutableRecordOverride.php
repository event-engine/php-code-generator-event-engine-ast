<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst;

use EventEngine\CodeGenerator\EventEngineAst\Config\Naming;
use EventEngine\CodeGenerator\EventEngineAst\Helper\MetadataTypeSetTrait;
use OpenCodeModeling\CodeAst\Builder\ClassBuilder;
use OpenCodeModeling\CodeAst\Builder\ClassMethodBuilder;
use OpenCodeModeling\CodeAst\Builder\ClassPropertyBuilder;
use OpenCodeModeling\CodeAst\Builder\FileCollection;
use OpenCodeModeling\CodeAst\Code\BodyGenerator;
use OpenCodeModeling\CodeAst\Code\MethodGenerator;
use OpenCodeModeling\CodeAst\Code\ParameterGenerator;
use OpenCodeModeling\CodeAst\Code\PropertyGenerator;

final class AggregateStateImmutableRecordOverride
{
    use MetadataTypeSetTrait;

    private Naming $config;

    public function __construct(Naming $config)
    {
        $this->config = $config;
    }

    /**
     * Adds methods to override default ImmutableRecord behaviour to pass through arbitrary data
     *
     * @param FileCollection $fileCollection
     */
    public function generateImmutableRecordOverride(FileCollection $fileCollection): void
    {
        foreach ($fileCollection as $classBuilder) {
            if (! $classBuilder instanceof ClassBuilder
                || ! $classBuilder->hasImplement('ImmutableRecord')
            ) {
                continue;
            }
            $classBuilder->addProperty(
                ClassPropertyBuilder::fromNode($this->stateProperty()->generate()),
            );

            $classBuilder->addMethod(
                ClassMethodBuilder::fromNode($this->fromRecordDataMethod()->generate()),
                ClassMethodBuilder::fromNode($this->fromArrayMethod()->generate()),
                ClassMethodBuilder::fromNode($this->withMethod()->generate()),
                ClassMethodBuilder::fromNode($this->toArrayMethod()->generate()),
                ClassMethodBuilder::fromNode($this->equalsMethod()->generate()),
                ClassMethodBuilder::fromNode($this->constructorMethod()->generate()),
                ClassMethodBuilder::fromNode($this->setRecordDataMethod()->generate()),
            );
        }
    }

    private function stateProperty(): PropertyGenerator
    {
        return (new PropertyGenerator(
            'state',
            'array',
        ))->setDefaultValue([]);
    }

    private function fromRecordDataMethod(): MethodGenerator
    {
        $method = new MethodGenerator(
            'fromRecordData',
            [new ParameterGenerator('recordData', 'array')],
            MethodGenerator::FLAG_PUBLIC | MethodGenerator::FLAG_STATIC,
            new BodyGenerator(
                $this->config->config()->getParser(),
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
                $this->config->config()->getParser(),
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
                $this->config->config()->getParser(),
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
                $this->config->config()->getParser(),
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
                $this->config->config()->getParser(),
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
                $this->config->config()->getParser(),
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
                $this->config->config()->getParser(),
                '$this->state = array_merge($this->state, $recordData);'
            )
        );
        $method->setReturnType('void');

        return $method;
    }
}
