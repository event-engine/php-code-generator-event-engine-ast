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
use EventEngine\CodeGenerator\EventEngineAst\NodeVisitor\ClassMethodDescribeAggregate;
use EventEngine\InspectioGraph\CommandType;
use EventEngine\InspectioGraph\Connection\AggregateConnectionAnalyzer;
use EventEngine\InspectioGraph\EventSourcingAnalyzer;
use OpenCodeModeling\CodeAst\Builder\ClassBuilder;
use OpenCodeModeling\CodeAst\Builder\ClassConstBuilder;
use OpenCodeModeling\CodeAst\Builder\ClassMethodBuilder;
use OpenCodeModeling\CodeAst\Builder\FileCollection;

final class Aggregate
{
    use MetadataTypeSetTrait;

    private Naming $config;

    private Code\AggregateDescription $aggregateDescription;
    private Code\AggregateBehaviourEventMethod $eventMethod;
    private Code\AggregateBehaviourCommandMethod $commandMethod;
    private Code\AggregateStateMethod $aggregateStateMethod;

    public function __construct(Naming $config)
    {
        $this->config = $config;

        $this->aggregateDescription = new Code\AggregateDescription(
            $this->config->config()->getParser(),
            $this->config->config()->getFilterConstName(),
            $this->config->getFilterAggregateIdName(),
            $this->config->getFilterCommandMethodName(),
            $this->config->getFilterEventMethodName()
        );

        $this->eventMethod = new Code\AggregateBehaviourEventMethod(
            $this->config->config()->getParser(),
            $this->config->getFilterEventMethodName(),
            $this->config->config()->getFilterParameterName()
        );

        $this->commandMethod = new Code\AggregateBehaviourCommandMethod(
            $this->config->config()->getParser(),
            $this->config->getFilterCommandMethodName(),
            $this->config->config()->getFilterParameterName()
        );

        $this->aggregateStateMethod = new Code\AggregateStateMethod(
            $this->config->config()->getParser(),
            $this->config->getFilterWithMethodName(),
            $this->config->config()->getFilterParameterName()
        );
    }

    /**
     * @param EventSourcingAnalyzer & AggregateConnectionAnalyzer $analyzer
     * @param FileCollection $files
     * @param string $apiFileName
     */
    public function generateApiDescription(
        $analyzer,
        FileCollection $files,
        string $apiFileName
    ): void {
        $fqcn = $this->config->getFullyQualifiedClassNameFromFilename($apiFileName);

        $classBuilder = ClassBuilder::fromScratch(
            $this->config->getClassNameFromFullyQualifiedClassName($fqcn),
            $this->config->getClassNamespaceFromFullyQualifiedClassName($fqcn)
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

        foreach ($analyzer->aggregateConnectionMap() as $name => $aggregateConnection) {
            $aggregate = $aggregateConnection->aggregate();

            $aggregateBehaviourFqcn = $this->config->getAggregateBehaviourFullyQualifiedClassName($aggregate, $analyzer);
            $aggregateBehaviourClassName = $this->config->getClassNameFromFullyQualifiedClassName($aggregateBehaviourFqcn);

            $storeStateIn = null;

            if ($filterStoreStateIn !== null) {
                $storeStateIn = ($filterStoreStateIn)($aggregate->label());
            }

            $classBuilder->addNamespaceImport($aggregateBehaviourFqcn);

            $commandsToEventsMap = $aggregateConnection->commandsToEventsMap();

            $classBuilder->addConstant(
                ClassConstBuilder::fromScratch(
                    ($this->config->config()->getFilterConstName())($aggregate->label()),
                    ($this->config->config()->getFilterConstValue())($aggregate->label()),
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
     * @param EventSourcingAnalyzer & AggregateConnectionAnalyzer $analyzer
     * @param FileCollection $fileCollection
     * @param string $apiEventFilename Filename for Event API
     */
    public function generateAggregateFile(
        $analyzer,
        FileCollection $fileCollection,
        string $apiEventFilename
    ): void {
        foreach ($analyzer->aggregateConnectionMap() as $name => $aggregateConnection) {
            $aggregate = $aggregateConnection->aggregate();

            $aggregateBehaviourFqcn = $this->config->getAggregateBehaviourFullyQualifiedClassName($aggregate, $analyzer);

            $aggregateStateFqcn = $this->config->getAggregateStateFullyQualifiedClassName($aggregate, $analyzer);
            $aggregateStateClassName = $this->config->getClassNameFromFullyQualifiedClassName($aggregateStateFqcn);

            $classBuilder = ClassBuilder::fromScratch(
                $this->config->getClassNameFromFullyQualifiedClassName($aggregateBehaviourFqcn),
                $this->config->getClassNamespaceFromFullyQualifiedClassName($aggregateBehaviourFqcn)
            )->setFinal(true);

            $classBuilder->addNamespaceImport(
                'EventEngine\Messaging\Message',
                'Generator',
                $aggregateStateFqcn,
                $this->config->getFullyQualifiedClassNameFromFilename($apiEventFilename)
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

            $fileCollection->add($classBuilder);
        }
    }

    /**
     * Generates aggregate state file for each aggregate with corresponding value objects depending on given JSON
     * schema metadata of the aggregate.
     *
     * @param EventSourcingAnalyzer & AggregateConnectionAnalyzer $analyzer
     * @param FileCollection $fileCollection
     */
    public function generateAggregateStateFile(
        $analyzer,
        FileCollection $fileCollection
    ): void {
        foreach ($analyzer->aggregateConnectionMap() as $name => $aggregateConnection) {
            $aggregate = $aggregateConnection->aggregate();

            $aggregateStateFqcn = $this->config->getAggregateStateFullyQualifiedClassName($aggregate, $analyzer);
            $aggregateStateClassName = $this->config->getClassNameFromFullyQualifiedClassName($aggregateStateFqcn);

            $files = $this->config->config()->getObjectGenerator()->generateImmutableRecord(
                $aggregateStateFqcn,
                $this->config->config()->determineValueObjectPath($aggregate, $analyzer),
                $this->config->config()->determineValueObjectSharedPath(),
                $this->getMetadataTypeSetFromVertex($aggregate)
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
}
