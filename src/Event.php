<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst;

use EventEngine\CodeGenerator\EventEngineAst\Code\EventDescription;
use EventEngine\CodeGenerator\EventEngineAst\Config\Naming;
use EventEngine\CodeGenerator\EventEngineAst\Exception\WrongVertexConnection;
use EventEngine\CodeGenerator\EventEngineAst\Helper\ApiDescriptionClassMapTrait;
use EventEngine\CodeGenerator\EventEngineAst\Helper\MetadataTypeSetTrait;
use EventEngine\CodeGenerator\EventEngineAst\Metadata\InspectioJson\EventMetadata;
use EventEngine\CodeGenerator\EventEngineAst\NodeVisitor\ClassMethodDescribeEvent;
use EventEngine\InspectioGraph\EventSourcingAnalyzer;
use EventEngine\InspectioGraph\EventType;
use EventEngine\InspectioGraph\VertexConnection;
use EventEngine\InspectioGraph\VertexType;
use OpenCodeModeling\CodeAst\Builder\FileCollection;

final class Event
{
    use MetadataTypeSetTrait;
    use ApiDescriptionClassMapTrait;

    private Naming $config;

    private EventDescription $eventDescription;

    public function __construct(Naming $config)
    {
        $this->config = $config;
        $this->eventDescription = new EventDescription(
            $this->config->config()->getParser(),
            $this->config->config()->getFilterConstName()
        );
    }

    public function generateJsonSchemaFiles(
        VertexConnection $connection,
        EventSourcingAnalyzer $analyzer,
        string $pathSchema
    ): array {
        if ($connection->identity()->type() !== VertexType::TYPE_EVENT) {
            throw WrongVertexConnection::forConnection($connection, VertexType::TYPE_EVENT);
        }

        /** @var EventType $event */
        $event = $connection->identity();

        $files = [];

        $pathSchema = \rtrim(\rtrim($pathSchema), '\/\\') . DIRECTORY_SEPARATOR;

        $metadata = $event->metadataInstance();

        if ($metadata === null || ! $metadata instanceof EventMetadata) {
            return $files;
        }
        $schema = $metadata->schema();

        if ($schema === null) {
            return $files;
        }

        $files[$event->name()] = [
            'filename' => $pathSchema . ($this->config->config()->getFilterConstName())($event->label()) . '.json',
            'code' => \json_encode($schema, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT),
        ];

        return $files;
    }

    public function generateApiDescription(
        VertexConnection $connection,
        EventSourcingAnalyzer $analyzer,
        FileCollection $files,
        string $jsonSchemaFileName = null
    ): void {
        $classBuilder = $this->generateApiDescriptionFor($connection, $analyzer, $files, VertexType::TYPE_EVENT);

        if ($jsonSchemaFileName !== null) {
            $this->addSchemaPathConstant($classBuilder, $jsonSchemaFileName);
        }

        /** @var EventType $event */
        $event = $connection->identity();

        $classBuilder->addNodeVisitor(
            new ClassMethodDescribeEvent(
                $this->eventDescription->generate($event, $jsonSchemaFileName)
            )
        );

        $files->add($classBuilder);
    }

    public function generateApiDescriptionClassMap(
        VertexConnection $connection,
        EventSourcingAnalyzer $analyzer,
        FileCollection $files
    ): void {
        $classBuilder = $this->generateApiDescriptionClassMapFor($connection, $analyzer, $files, VertexType::TYPE_EVENT);

        $files->add($classBuilder);
    }

    /**
     * Generates event files with corresponding value objects depending on given JSON schema metadata.
     *
     * @param VertexConnection $connection
     * @param EventSourcingAnalyzer $analyzer
     * @param FileCollection $fileCollection
     */
    public function generateEventFile(
        VertexConnection $connection,
        EventSourcingAnalyzer $analyzer,
        FileCollection $fileCollection
    ): void {
        if ($connection->identity()->type() !== VertexType::TYPE_EVENT) {
            throw WrongVertexConnection::forConnection($connection, VertexType::TYPE_EVENT);
        }

        /** @var EventType $event */
        $event = $connection->identity();
        $typeSet = $this->getMetadataTypeSetFromVertex($event);

        $eventFqcn = $this->config->getFullyQualifiedClassName($event, $analyzer);

        $code = $this->config->config()->getObjectGenerator()->generateImmutableRecord(
            $eventFqcn,
            $this->config->config()->determineValueObjectPath($event, $analyzer),
            $this->config->config()->determineValueObjectSharedPath(),
            $typeSet
        );

        foreach ($code as $file) {
            $fileCollection->add($file);
        }
    }
}
