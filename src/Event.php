<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst;

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
    private Config\Event $config;

    private \EventEngine\CodeGenerator\EventEngineAst\Code\EventDescription $eventDescription;

    public function __construct(Config\Event $config)
    {
        $this->config = $config;
        $this->eventDescription = new \EventEngine\CodeGenerator\EventEngineAst\Code\EventDescription(
            $this->config->getParser(),
            $this->config->getFilterConstName()
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
                'filename' => $pathSchema . ($this->config->getFilterConstName())($eventVertex->label()) . '.json',
                'code' => $schema,
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
        $classInfo = $this->config->getClassInfoList()->classInfoForFilename($apiFileName);
        $fqcn = $classInfo->getFullyQualifiedClassNameFromFilename($apiFileName);

        $classBuilder = ClassBuilder::fromScratch(
            $classInfo->getClassName($fqcn),
            $classInfo->getClassNamespace($fqcn)
        );

        $classBuilder->addNamespaceImport(
            'EventEngine\EventEngine',
            'EventEngine\EventEngineDescription',
            'EventEngine\JsonSchema\JsonSchema',
            'EventEngine\JsonSchema\JsonSchemaArray'
        );

        $classBuilder->addImplement('EventEngineDescription');

        $classBuilder->addMethod(
            ClassMethodBuilder::fromNode(
                \EventEngine\CodeGenerator\EventEngineAst\Code\DescriptionFileMethod::generate()->generate()
            )
        );

        foreach ($analyzer->eventMap() as $name => $event) {
            $classBuilder->addConstant(
                ClassConstBuilder::fromScratch(
                    ($this->config->getFilterConstName())($event->label()),
                    ($this->config->getFilterConstValue())($event->label()),
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
            $pathEvent = $this->config->determinePath($event, $analyzer);

            $metadataInstance = $event->metadataInstance();

            $typeSet = null;

            if ($metadataInstance instanceof HasTypeSet
                && $metadataInstance->typeSet() !== null
            ) {
                $typeSet = $metadataInstance->typeSet();
            }

            $code = $this->config->getObjectGenerator()->generateImmutableRecord(
                $event->label(),
                $pathEvent,
                $this->config->determineValueObjectPath($event, $analyzer),
                $typeSet
            );

            foreach ($code as $file) {
                $fileCollection->add($file);
            }
        }
    }
}
