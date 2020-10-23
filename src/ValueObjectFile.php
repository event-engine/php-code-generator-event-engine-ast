<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine;

use EventEngine\CodeGenerator\Cartridge\EventEngine\Code\Metadata\JsonSchema;
use EventEngine\CodeGenerator\Cartridge\EventEngine\Exception\RuntimeException;
use EventEngine\InspectioGraph\AggregateConnection;
use EventEngine\InspectioGraph\EventSourcingAnalyzer;
use OpenCodeModeling\CodeAst\Code\ClassGenerator;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassFile;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassNamespace;
use OpenCodeModeling\CodeAst\NodeVisitor\StrictType;
use OpenCodeModeling\CodeGenerator\Code\ClassInfoList;
use OpenCodeModeling\JsonSchemaToPhp\Type\ObjectType;
use OpenCodeModeling\JsonSchemaToPhp\Type\TypeSet;
use OpenCodeModeling\JsonSchemaToPhpAst\ValueObjectFactory as AstValueObjectFactory;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\PrettyPrinterAbstract;

final class ValueObjectFile
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
    private $filterValueObjectPath;

    /**
     * @var ClassInfoList
     **/
    private $classInfoList;

    /**
     * @var AstValueObjectFactory
     **/
    private $valueObjectFactory;

    public function __construct(
        Parser $parser,
        PrettyPrinterAbstract $printer,
        ClassInfoList $classInfoList,
        AstValueObjectFactory $valueObjectFactory,
        callable $filterClassName,
        ?callable $filterValueObjectPath
    ) {
        $this->parser = $parser;
        $this->printer = $printer;
        $this->classInfoList = $classInfoList;
        $this->valueObjectFactory = $valueObjectFactory;
        $this->filterClassName = $filterClassName;
        $this->filterValueObjectPath = $filterValueObjectPath;
    }

    /**
     * @param EventSourcingAnalyzer $analyzer
     * @param string $path
     * @return array Assoc array with ValueObject name and file content
     */
    public function __invoke(EventSourcingAnalyzer $analyzer, string $path): array
    {
        $files = [];

        $classInfo = $this->classInfoList->classInfoForPath($path);

        /** @var AggregateConnection $aggregateConnection */
        foreach ($analyzer->aggregateMap() as $name => $aggregateConnection) {
            $pathValueObject = $path;

            if ($this->filterValueObjectPath !== null) {
                $pathValueObject .= DIRECTORY_SEPARATOR . ($this->filterValueObjectPath)($aggregateConnection->aggregate()->label());
            }

            foreach ($aggregateConnection->commandMap() as $command) {
                $jsonSchema = JsonSchema::fromVertex($command);

                $type = $jsonSchema->type();

                if ($type === null) {
                    continue;
                }

                $type = $type->first();

                if (! $type instanceof ObjectType) {
                    throw new RuntimeException(
                        \sprintf(
                            'Need type of "%s", type "%s" given',
                            ObjectType::class,
                            \get_class($type)
                        )
                    );
                }

                $definitions = $type->definitions();

                /** @var TypeSet $definitionTypeSet */
                foreach ($definitions as $definitionTypeSet) {
                    if (\count($definitionTypeSet) !== 1) {
                        throw new RuntimeException('Can only handle one type');
                    }

                    $definitionType = $definitionTypeSet->first();

                    $className = ($this->filterClassName)($definitionType->name());

                    $filename = $classInfo->getFilenameFromPathAndName($pathValueObject, $className);

                    $code = '';

                    if (\file_exists($filename) && \is_readable($filename)) {
                        $code = \file_get_contents($filename);
                    }
                    $ast = $this->parser->parse($code);
                    $valueObjectClass = new ClassGenerator($className);
                    $valueObjectClass->setFinal(true);

                    // order is important
                    $ValueObjectTraverser = new NodeTraverser();
                    $ValueObjectTraverser->addVisitor(new StrictType());
                    $ValueObjectTraverser->addVisitor(new ClassNamespace($classInfo->getClassNamespaceFromPath($pathValueObject)));
                    $ValueObjectTraverser->addVisitor(new ClassFile($valueObjectClass));

                    foreach ($this->valueObjectFactory->nodeVisitors($definitionType) as $nodeVisitor) {
                        $ValueObjectTraverser->addVisitor($nodeVisitor);
                    }

                    $files[$definitionType->name()] = [
                        'filename' => $filename,
                        'code' => $this->printer->prettyPrintFile($ValueObjectTraverser->traverse($ast)),
                    ];
                }
            }
        }

        return $files;
    }

    public static function workflowComponentDescription(
        Parser $parser,
        PrettyPrinterAbstract $printer,
        ClassInfoList $classInfoList,
        \OpenCodeModeling\JsonSchemaToPhpAst\ValueObjectFactory $valueObjectFactory,
        callable $filterClassName,
        ?callable $filterValueObjectPath,
        string $inputAnalyzer,
        string $inputPath,
        string $output
    ): \OpenCodeModeling\CodeGenerator\Workflow\Description {
        $instance = new self(
            $parser,
            $printer,
            $classInfoList,
            $valueObjectFactory,
            $filterClassName,
            $filterValueObjectPath
        );

        return new \OpenCodeModeling\CodeGenerator\Workflow\ComponentDescriptionWithSlot(
            $instance,
            $output,
            $inputAnalyzer,
            $inputPath
        );
    }
}
