<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst;

use EventEngine\CodeGenerator\EventEngineAst\Code\DescriptionFileMethod;
use EventEngine\CodeGenerator\EventEngineAst\Code\EventDescription;
use EventEngine\CodeGenerator\EventEngineAst\Config\Naming;
use EventEngine\CodeGenerator\EventEngineAst\Metadata\HasTypeSet;
use EventEngine\CodeGenerator\EventEngineAst\Metadata\InspectioJson\EventMetadata;
use EventEngine\CodeGenerator\EventEngineAst\NodeVisitor\ClassMethodDescribeEvent;
use EventEngine\InspectioGraph\EventSourcingAnalyzer;
use OpenCodeModeling\CodeAst\Builder\ClassBuilder;
use OpenCodeModeling\CodeAst\Builder\ClassConstBuilder;
use OpenCodeModeling\CodeAst\Builder\ClassMethodBuilder;
use OpenCodeModeling\CodeAst\Builder\FileCollection;

final class Event
{
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

    public function generateJsonSchemaFiles(EventSourcingAnalyzer $analyzer, string $pathSchema): array
    {
        $files = [];

        $pathSchema = \rtrim(\rtrim($pathSchema), '\/\\') . DIRECTORY_SEPARATOR;

        foreach ($analyzer->eventMap() as $name => $eventVertex) {
            $metadata = $eventVertex->metadataInstance();

            if ($metadata === null || ! $metadata instanceof EventMetadata) {
                continue;
            }
            $schema = $metadata->schema();

            if ($schema === null) {
                continue;
            }

            $files[$name] = [
                'filename' => $pathSchema . ($this->config->config()->getFilterConstName())($eventVertex->label()) . '.json',
                'code' => \json_encode($schema, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT),
            ];
        }

        return $files;
    }

    public function generateApiDescription(
        EventSourcingAnalyzer $analyzer,
        FileCollection $files,
        string $apiFileName,
        string $jsonSchemaFileName = null
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
                DescriptionFileMethod::generate()->generate()
            )
        );

        foreach ($analyzer->eventMap() as $name => $event) {
            $classBuilder->addConstant(
                ClassConstBuilder::fromScratch(
                    ($this->config->config()->getFilterConstName())($event->label()),
                    ($this->config->config()->getFilterConstValue())($event->label()),
                )
            );

            $classBuilder->addNodeVisitor(
                new ClassMethodDescribeEvent(
                    $this->eventDescription->generate($event, $jsonSchemaFileName)
                )
            );
        }

        $files->add($classBuilder);
    }

    /**
     * Generates event files with corresponding value objects depending on given JSON schema metadata.
     *
     * @param EventSourcingAnalyzer $analyzer
     * @param FileCollection $fileCollection
     */
    public function generateEventFile(EventSourcingAnalyzer $analyzer, FileCollection $fileCollection): void
    {
        foreach ($analyzer->eventMap() as $name => $event) {
            $metadataInstance = $event->metadataInstance();

            $typeSet = null;

            if ($metadataInstance instanceof HasTypeSet
                && $metadataInstance->typeSet() !== null
            ) {
                $typeSet = $metadataInstance->typeSet();
            }

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
}
