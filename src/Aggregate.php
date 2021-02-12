<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst;

use EventEngine\CodeGenerator\EventEngineAst\Metadata\HasTypeSet;
use EventEngine\CodeGenerator\EventEngineAst\NodeVisitor\ClassMethodDescribeAggregate;
use EventEngine\InspectioGraph\AggregateType;
use EventEngine\InspectioGraph\CommandType;
use EventEngine\InspectioGraph\EventSourcingAnalyzer;
use EventEngine\InspectioGraph\VertexType;
use OpenCodeModeling\CodeAst\Builder\ClassBuilder;
use OpenCodeModeling\CodeAst\Builder\ClassConstBuilder;
use OpenCodeModeling\CodeAst\Builder\ClassMethodBuilder;
use OpenCodeModeling\CodeAst\Builder\FileCollection;
use OpenCodeModeling\JsonSchemaToPhp\Type\TypeSet;

final class Aggregate
{
    private Config\Aggregate $config;

    private Code\AggregateDescription $aggregateDescription;
    private Code\AggregateBehaviourEventMethod $eventMethod;
    private Code\AggregateBehaviourCommandMethod $commandMethod;
    private Code\AggregateStateMethod $aggregateStateMethod;

    public function __construct(Config\Aggregate $config)
    {
        $this->config = $config;

        $this->aggregateDescription = new Code\AggregateDescription(
            $this->config->getParser(),
            $this->config->getFilterConstName(),
            $this->config->getFilterAggregateIdName(),
            $this->config->getFilterCommandMethodName(),
            $this->config->getFilterEventMethodName()
        );

        $this->eventMethod = new Code\AggregateBehaviourEventMethod(
            $this->config->getParser(),
            $this->config->getFilterEventMethodName(),
            $this->config->getFilterParameterName()
        );

        $this->commandMethod = new Code\AggregateBehaviourCommandMethod(
            $this->config->getParser(),
            $this->config->getFilterCommandMethodName(),
            $this->config->getFilterParameterName()
        );

        $this->aggregateStateMethod = new Code\AggregateStateMethod(
            $this->config->getParser(),
            $this->config->getFilterWithMethodName(),
            $this->config->getFilterParameterName()
        );
    }

    public function generateApiDescription(
        EventSourcingAnalyzer $analyzer,
        FileCollection $files,
        string $apiFileName
    ): void {
        $classInfo = $this->config->getClassInfoList()->classInfoForFilename($apiFileName);
        $fqcn = $classInfo->getFullyQualifiedClassNameFromFilename($apiFileName);

        $classBuilder = ClassBuilder::fromScratch(
            $classInfo->getClassName($fqcn),
            $classInfo->getClassNamespace($fqcn)
        )->setFinal(true);

        $classBuilder->addNamespaceImport(
            'EventEngine\EventEngine',
            'EventEngine\EventEngineDescription',
            'EventEngine\JsonSchema\JsonSchema',
            'EventEngine\JsonSchema\JsonSchemaArray'
        );

        $classBuilder->addImplement('EventEngineDescription');

        $classBuilder->addMethod(
            ClassMethodBuilder::fromNode(
                Code\DescriptionFileMethod::generate()->generate()
            )
        );

        $filterStoreStateIn = $this->config->getFilterAggregateStoreStateIn();

        foreach ($analyzer->aggregateMap() as $name => $aggregateConnection) {
            $aggregate = $aggregateConnection->aggregate();

            $aggregateBehaviourClassName = ($this->config->getFilterClassName())($aggregate->label());

            $pathAggregate = $this->config->determinePath($aggregate, $analyzer);
            $storeStateIn = null;

            if ($filterStoreStateIn !== null) {
                $storeStateIn = ($filterStoreStateIn)($aggregate->label());
            }

            $filename = $classInfo->getFilenameFromPathAndName($pathAggregate, $aggregateBehaviourClassName);
            $classBuilder->addNamespaceImport($classInfo->getFullyQualifiedClassNameFromFilename($filename));

            $commandsToEventsMap = $aggregateConnection->commandsToEventsMap();

            $classBuilder->addConstant(
                ClassConstBuilder::fromScratch(
                    ($this->config->getFilterConstName())($aggregate->label()),
                    ($this->config->getFilterConstValue())($aggregate->label()),
                )
            );

            /** @var CommandType $commandVertex */
            foreach ($commandsToEventsMap as $commandVertex) {
                $classBuilder->addNodeVisitor(
                    new ClassMethodDescribeAggregate(
                        $this->aggregateDescription->generate(
                            $aggregateBehaviourClassName,
                            $aggregateBehaviourClassName,
                            $storeStateIn,
                            $commandVertex,
                            $aggregate,
                            ...$commandsToEventsMap[$commandVertex]
                        )
                    )
                );
            }
        }

        $files->add($classBuilder);
    }

    /**
     * Generates aggregate files with corresponding value objects depending on given JSON schema metadata.
     *
     * @param EventSourcingAnalyzer $analyzer
     * @param FileCollection $fileCollection
     * @param string $apiEventFilename Filename for Event API
     */
    public function generateAggregateFile(
        EventSourcingAnalyzer $analyzer,
        FileCollection $fileCollection,
        string $apiEventFilename
    ): void {
        foreach ($analyzer->aggregateMap() as $name => $aggregateConnection) {
            $aggregate = $aggregateConnection->aggregate();

            $aggregateBehaviourClassName = ($this->config->getFilterClassName())($aggregate->label());
            $pathAggregate = $this->config->determinePath($aggregate, $analyzer);

            $classInfo = $this->config->getClassInfoList()->classInfoForPath($pathAggregate);

            $filename = $classInfo->getFilenameFromPathAndName($pathAggregate, $aggregateBehaviourClassName);
            $fqcn = $classInfo->getFullyQualifiedClassNameFromFilename($filename);

            $aggregateStateClassName = ($this->config->getFilterAggregateStateClassName())($aggregate->label());

            $classBuilder = ClassBuilder::fromScratch(
                $classInfo->getClassName($fqcn),
                $classInfo->getClassNamespace($fqcn)
            )->setFinal(true);

            $classBuilder->addNamespaceImport(
                'EventEngine\Messaging\Message',
                'Generator',
                $classInfo->getFullyQualifiedClassNameFromFilename(
                    $classInfo->getFilenameFromPathAndName($pathAggregate, $aggregateStateClassName)
                ),
                $classInfo->getFullyQualifiedClassNameFromFilename($apiEventFilename)
            );

            $commandsToEventsMap = $aggregateConnection->commandsToEventsMap();

            /** @var \EventEngine\InspectioGraph\CommandType $commandVertex */
            foreach ($commandsToEventsMap as $commandVertex) {
                $classBuilder->addMethod(
                    ClassMethodBuilder::fromNode(
                        $this->commandMethod->generate(
                            $aggregate,
                            $commandVertex,
                            ...$commandsToEventsMap[$commandVertex]
                        )->generate()
                    )
                );
                /** @var \EventEngine\InspectioGraph\EventType $eventVertex */
                foreach ($commandsToEventsMap[$commandVertex] as $eventVertex) {
                    $classBuilder->addMethod(
                        ClassMethodBuilder::fromNode(
                            $this->eventMethod->generate(
                                $aggregate,
                                $commandVertex,
                                $eventVertex,
                                $aggregateStateClassName
                            )->generate()
                        )
                    );
                }
            }

            $this->generateValueObjects($fileCollection, $aggregate, $analyzer);

            $fileCollection->add($classBuilder);
        }
    }

    /**
     * Generates aggregate state file for each aggregate with corresponding value objects depending on given JSON
     * schema metadata of the aggregate.
     *
     * @param EventSourcingAnalyzer $analyzer
     * @param FileCollection $fileCollection
     */
    public function generateAggregateStateFile(
        EventSourcingAnalyzer $analyzer,
        FileCollection $fileCollection
    ): void {
        foreach ($analyzer->aggregateMap() as $name => $aggregateConnection) {
            $aggregate = $aggregateConnection->aggregate();

            $aggregateStateClassName = ($this->config->getFilterAggregateStateClassName())($aggregate->label());
            $pathAggregateState = $this->config->determinePath($aggregate, $analyzer);

            $files = $this->config->getObjectGenerator()->generateImmutableRecord(
                $aggregateStateClassName,
                $pathAggregateState,
                $this->config->determineValueObjectPath($aggregate, $analyzer),
                $this->getMetadata($aggregate)
            );

            foreach ($aggregateConnection->eventMap() as $event) {
                /** @var ClassBuilder $aggregateState */
                foreach ($files->filter(fn (ClassBuilder $classBuilder) => $classBuilder->getName() === $aggregateStateClassName) as $aggregateState) {
                    $aggregateState->addMethod(
                        ClassMethodBuilder::fromNode(
                            $this->aggregateStateMethod->generate($event)->generate()
                        )
                    );
                }
            }

            foreach ($files as $file) {
                $fileCollection->add($file);
            }
        }
    }

    private function generateValueObjects(
        FileCollection $fileCollection,
        AggregateType $aggregate,
        EventSourcingAnalyzer $analyzer
    ): void {
        if ($typeSet = $this->getMetadata($aggregate)) {
            $valueObjects = $this->config->getObjectGenerator()->generateValueObjectsFromObjectProperties(
                $this->config->determineValueObjectPath($aggregate, $analyzer),
                $typeSet
            );

            foreach ($valueObjects as $file) {
                $fileCollection->add($file);
            }
        }
    }

    private function getMetadata(VertexType $vertexType): ?TypeSet
    {
        $metadataInstance = $vertexType->metadataInstance();

        return $metadataInstance instanceof HasTypeSet ? $metadataInstance->typeSet() : null;
    }
}
