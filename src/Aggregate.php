<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst;

use EventEngine\CodeGenerator\EventEngineAst\Config\Naming;
use EventEngine\CodeGenerator\EventEngineAst\Exception\MissingMetadata;
use EventEngine\CodeGenerator\EventEngineAst\Exception\WrongVertexConnection;
use EventEngine\CodeGenerator\EventEngineAst\Helper\ApiDescriptionClassMapTrait;
use EventEngine\CodeGenerator\EventEngineAst\Helper\FindAggregateStateTrait;
use EventEngine\CodeGenerator\EventEngineAst\Helper\MetadataTypeSetTrait;
use EventEngine\CodeGenerator\EventEngineAst\Metadata\AggregateMetadata;
use EventEngine\CodeGenerator\EventEngineAst\Metadata\CommandMetadata;
use EventEngine\CodeGenerator\EventEngineAst\Metadata\DocumentMetadata;
use EventEngine\CodeGenerator\EventEngineAst\NodeVisitor\ClassMethodDescribeAggregate;
use EventEngine\InspectioGraph\AggregateType;
use EventEngine\InspectioGraph\CommandType;
use EventEngine\InspectioGraph\EventSourcingAnalyzer;
use EventEngine\InspectioGraph\VertexConnection;
use EventEngine\InspectioGraph\VertexConnectionMap;
use EventEngine\InspectioGraph\VertexType;
use OpenCodeModeling\CodeAst\Builder\ClassBuilder;
use OpenCodeModeling\CodeAst\Builder\ClassMethodBuilder;
use OpenCodeModeling\CodeAst\Builder\FileCollection;

final class Aggregate
{
    use MetadataTypeSetTrait;
    use FindAggregateStateTrait;
    use ApiDescriptionClassMapTrait;

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

    public function generateApiDescription(
        VertexConnection $connection,
        EventSourcingAnalyzer $analyzer,
        FileCollection $files
    ): void {
        $classBuilder = $this->generateApiDescriptionFor($connection, $analyzer, $files, VertexType::TYPE_AGGREGATE);

        /** @var AggregateType $aggregate */
        $aggregate = $connection->identity();

        $aggregateMetadata = $aggregate->metadataInstance();

        if (! $aggregateMetadata instanceof AggregateMetadata) {
            throw MissingMetadata::forVertex($aggregate, AggregateMetadata::class);
        }

        $aggregateBehaviourFqcn = $this->config->getAggregateBehaviourFullyQualifiedClassName($aggregate, $analyzer);
        $aggregateBehaviourClassName = $this->config->getClassNameFromFullyQualifiedClassName($aggregateBehaviourFqcn);

        $classBuilder->addNamespaceImport($aggregateBehaviourFqcn);

        $commands = $connection->from()->filterByType(VertexType::TYPE_COMMAND);
        $events = $connection->to()->filterByType(VertexType::TYPE_EVENT);

        /** @var CommandType $command */
        foreach ($commands as $command) {
            $commandMetadataInstance = $command->metadataInstance();

            $storeStateIn = null;

            if ($commandMetadataInstance instanceof CommandMetadata
                && true === $commandMetadataInstance->newAggregate()
                && ($aggregateState = $this->findAggregateState($command->id(), VertexConnectionMap::WALK_FORWARD, $analyzer))
            ) {
                $aggregateStateMetadata = $aggregateState->identity()->metadataInstance();

                if ($aggregateStateMetadata instanceof DocumentMetadata) {
                    $storeStateIn = $aggregateStateMetadata->customData()['collection'] ?? null;
                }
            }

            $classBuilder->addNodeVisitor(
                new ClassMethodDescribeAggregate(
                    $this->aggregateDescription->generate(
                        $aggregateBehaviourClassName,
                        $aggregateBehaviourClassName,
                        $storeStateIn,
                        $aggregateMetadata->stream(),
                        $command,
                        $aggregate,
                        // @phpstan-ignore-next-line
                        ...$events->vertices()
                    )
                )
            );
        }

        $files->add($classBuilder);
    }

    public function generateApiDescriptionClassMap(
        VertexConnection $connection,
        EventSourcingAnalyzer $analyzer,
        FileCollection $files
    ): void {
        $classBuilder = $this->generateApiDescriptionClassMapFor($connection, $analyzer, $files, VertexType::TYPE_AGGREGATE);

        $files->add($classBuilder);
    }

    /**
     * Generates aggregate files with corresponding value objects depending on given JSON schema metadata.
     *
     * @param VertexConnection $connection
     * @param EventSourcingAnalyzer $analyzer
     * @param FileCollection $fileCollection
     */
    public function generateAggregateFile(
        VertexConnection $connection,
        EventSourcingAnalyzer $analyzer,
        FileCollection $fileCollection
    ): void {
        if ($connection->identity()->type() !== VertexType::TYPE_AGGREGATE) {
            throw WrongVertexConnection::forConnection($connection, VertexType::TYPE_AGGREGATE);
        }
        /** @var AggregateType $aggregate */
        $aggregate = $connection->identity();

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
            $aggregateStateFqcn
        );

        $commands = $connection->from()->filterByType(VertexType::TYPE_COMMAND);
        $events = $connection->to()->filterByType(VertexType::TYPE_EVENT);

        /** @var \EventEngine\InspectioGraph\CommandType $command */
        foreach ($commands as $command) {
            $classBuilder->addNamespaceImport($this->config->getApiDescriptionFullyQualifiedClassName($command, $analyzer));
            $classBuilder->addMethod(
                ClassMethodBuilder::fromNode(
                    $this->commandMethod->generate(
                        $aggregate,
                        $command,
                        // @phpstan-ignore-next-line
                        ...$events->vertices()
                    )->generate(),
                    true,
                    $this->config->config()->getPrinter()
                )
            );
            /** @var \EventEngine\InspectioGraph\EventType $event */
            foreach ($events as $event) {
                $classBuilder->addNamespaceImport($this->config->getApiDescriptionFullyQualifiedClassName($event, $analyzer));
                $classBuilder->addMethod(
                    ClassMethodBuilder::fromNode(
                        $this->eventMethod->generate(
                            $aggregate,
                            $command,
                            $event,
                            $aggregateStateClassName
                        )->generate(),
                        true,
                        $this->config->config()->getPrinter()
                    )
                );
            }
        }

        $fileCollection->add($classBuilder);
    }

    /**
     * Generates aggregate state file for each aggregate with corresponding value objects depending on given JSON
     * schema metadata of the aggregate.
     *
     * @param VertexConnection $connection
     * @param EventSourcingAnalyzer $analyzer
     * @param FileCollection $fileCollection
     */
    public function generateAggregateStateFile(
        VertexConnection $connection,
        EventSourcingAnalyzer $analyzer,
        FileCollection $fileCollection
    ): void {
        if ($connection->identity()->type() !== VertexType::TYPE_AGGREGATE) {
            throw WrongVertexConnection::forConnection($connection, VertexType::TYPE_AGGREGATE);
        }
        /** @var AggregateType $aggregate */
        $aggregate = $connection->identity();
        $events = $connection->to()->filterByType(VertexType::TYPE_EVENT);

        $aggregateStateFqcn = $this->config->getAggregateStateFullyQualifiedClassName($aggregate, $analyzer);
        $aggregateStateClassName = $this->config->getClassNameFromFullyQualifiedClassName($aggregateStateFqcn);

        $files = $this->config->config()->getObjectGenerator()->generateImmutableRecord(
            $aggregateStateFqcn,
            $this->config->config()->determineValueObjectPath($aggregate, $analyzer),
            $this->config->config()->determineValueObjectSharedPath(),
            $this->getMetadataTypeSetFromVertex($aggregate)
        );

        foreach ($events as $event) {
            /** @var ClassBuilder $aggregateState */
            foreach ($files->filter(fn (ClassBuilder $classBuilder) => $classBuilder->getName() === $aggregateStateClassName) as $aggregateState) {
                $aggregateState->addMethod(
                    ClassMethodBuilder::fromNode(
                        $this->aggregateStateMethod->generate($event)->generate(),
                        true,
                        $this->config->config()->getPrinter()
                    )
                );
            }
        }

        foreach ($files as $file) {
            $fileCollection->add($file);
        }
    }
}
