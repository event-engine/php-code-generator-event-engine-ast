<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst;

use EventEngine\CodeGenerator\EventEngineAst\Metadata\HasTypeSet;
use EventEngine\CodeGenerator\EventEngineAst\Metadata\InspectioJson\CommandMetadata;
use EventEngine\CodeGenerator\EventEngineAst\NodeVisitor\ClassMethodDescribeCommand;
use EventEngine\InspectioGraph\EventSourcingAnalyzer;
use OpenCodeModeling\CodeAst\Builder\ClassBuilder;
use OpenCodeModeling\CodeAst\Builder\ClassConstBuilder;
use OpenCodeModeling\CodeAst\Builder\ClassMethodBuilder;
use OpenCodeModeling\CodeAst\Builder\FileCollection;

final class Command
{
    private Config\Command $config;

    private \EventEngine\CodeGenerator\EventEngineAst\Code\CommandDescription $commandDescription;

    public function __construct(Config\Command $config)
    {
        $this->config = $config;
        $this->commandDescription = new \EventEngine\CodeGenerator\EventEngineAst\Code\CommandDescription(
            $this->config->getParser(),
            $this->config->getFilterConstName()
        );
    }

    public function generateJsonSchemaFiles(EventSourcingAnalyzer $analyzer, string $pathSchema): array
    {
        $files = [];

        $pathSchema = \rtrim(\rtrim($pathSchema), '\/\\') . DIRECTORY_SEPARATOR;

        foreach ($analyzer->commandMap() as $name => $commandVertex) {
            $metadata = $commandVertex->metadataInstance();

            if ($metadata === null || ! $metadata instanceof CommandMetadata) {
                continue;
            }
            $schema = $metadata->schema();

            if ($schema === null) {
                continue;
            }

            $files[$name] = [
                'filename' => $pathSchema . ($this->config->getFilterConstName())($commandVertex->label()) . '.json',
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
                \EventEngine\CodeGenerator\EventEngineAst\Code\DescriptionFileMethod::generate()->generate()
            )
        );

        foreach ($analyzer->commandMap() as $name => $command) {
            $classBuilder->addConstant(
                ClassConstBuilder::fromScratch(
                    ($this->config->getFilterConstName())($command->label()),
                    ($this->config->getFilterConstValue())($command->label()),
                )
            );

            $classBuilder->addNodeVisitor(
                new ClassMethodDescribeCommand(
                    $this->commandDescription->generate($command, $jsonSchemaFileName)
                )
            );
        }

        $files->add($classBuilder);
    }

    /**
     * Generates command files with corresponding value objects depending on given JSON schema metadata.
     *
     * @param EventSourcingAnalyzer $analyzer
     * @param FileCollection $fileCollection
     */
    public function generateCommandFile(EventSourcingAnalyzer $analyzer, FileCollection $fileCollection): void
    {
        foreach ($analyzer->commandMap() as $name => $command) {
            $pathCommand = $this->config->determinePath($command, $analyzer);

            $metadataInstance = $command->metadataInstance();

            $typeSet = null;

            if ($metadataInstance instanceof HasTypeSet
                && $metadataInstance->typeSet() !== null
            ) {
                $typeSet = $metadataInstance->typeSet();
            }

            $code = $this->config->getObjectGenerator()->generateImmutableRecord(
                $command->label(),
                $pathCommand,
                $this->config->determineValueObjectPath($command, $analyzer),
                $typeSet
            );

            foreach ($code as $file) {
                $fileCollection->add($file);
            }
        }
    }
}
