<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst;

use EventEngine\CodeGenerator\EventEngineAst\Code\CommandDescription;
use EventEngine\CodeGenerator\EventEngineAst\Config\Naming;
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
    private Naming $config;

    private CommandDescription $commandDescription;

    public function __construct(Naming $config)
    {
        $this->config = $config;
        $this->commandDescription = new CommandDescription(
            $this->config->config()->getParser(),
            $this->config->config()->getFilterConstName()
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
                'filename' => $pathSchema . ($this->config->config()->getFilterConstName())($commandVertex->label()) . '.json',
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
                \EventEngine\CodeGenerator\EventEngineAst\Code\DescriptionFileMethod::generate()->generate()
            )
        );

        foreach ($analyzer->commandMap() as $name => $command) {
            $classBuilder->addConstant(
                ClassConstBuilder::fromScratch(
                    ($this->config->config()->getFilterConstName())($command->label()),
                    ($this->config->config()->getFilterConstValue())($command->label()),
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
            $metadataInstance = $command->metadataInstance();

            $typeSet = null;

            if ($metadataInstance instanceof HasTypeSet
                && $metadataInstance->typeSet() !== null
            ) {
                $typeSet = $metadataInstance->typeSet();
            }

            $commandFqcn = $this->config->getFullyQualifiedClassName($command, $analyzer);

            $code = $this->config->config()->getObjectGenerator()->generateImmutableRecord(
                $commandFqcn,
                $this->config->config()->determineValueObjectPath($command, $analyzer),
                $this->config->config()->determineValueObjectSharedPath(),
                $typeSet
            );

            foreach ($code as $file) {
                $fileCollection->add($file);
            }
        }
    }
}
