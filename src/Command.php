<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst;

use EventEngine\CodeGenerator\EventEngineAst\Code\CommandDescription;
use EventEngine\CodeGenerator\EventEngineAst\Code\DescriptionFileMethod;
use EventEngine\CodeGenerator\EventEngineAst\Config\Naming;
use EventEngine\CodeGenerator\EventEngineAst\Exception\WrongVertexConnection;
use EventEngine\CodeGenerator\EventEngineAst\Helper\MetadataTypeSetTrait;
use EventEngine\CodeGenerator\EventEngineAst\Metadata\InspectioJson\CommandMetadata;
use EventEngine\CodeGenerator\EventEngineAst\NodeVisitor\ClassMethodDescribeCommand;
use EventEngine\InspectioGraph\CommandType;
use EventEngine\InspectioGraph\EventSourcingAnalyzer;
use EventEngine\InspectioGraph\VertexConnection;
use EventEngine\InspectioGraph\VertexType;
use OpenCodeModeling\CodeAst\Builder\ClassBuilder;
use OpenCodeModeling\CodeAst\Builder\ClassConstBuilder;
use OpenCodeModeling\CodeAst\Builder\ClassMethodBuilder;
use OpenCodeModeling\CodeAst\Builder\FileCollection;

final class Command
{
    use MetadataTypeSetTrait;

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

    public function generateJsonSchemaFiles(
        VertexConnection $connection,
        EventSourcingAnalyzer $analyzer,
        string $pathSchema
    ): array {
        if ($connection->identity()->type() !== VertexType::TYPE_COMMAND) {
            throw WrongVertexConnection::forConnection($connection, VertexType::TYPE_COMMAND);
        }

        $files = [];

        $pathSchema = \rtrim(\rtrim($pathSchema), '\/\\') . DIRECTORY_SEPARATOR;

        /** @var CommandType $command */
        $command = $connection->identity();

        $metadata = $command->metadataInstance();

        if ($metadata === null || ! $metadata instanceof CommandMetadata) {
            return $files;
        }
        $schema = $metadata->schema();

        if ($schema === null) {
            return $files;
        }

        $files[$command->name()] = [
            'filename' => $pathSchema . ($this->config->config()->getFilterConstName())($command->label()) . '.json',
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
        if ($connection->identity()->type() !== VertexType::TYPE_COMMAND) {
            throw WrongVertexConnection::forConnection($connection, VertexType::TYPE_COMMAND);
        }

        $fqcn = $this->config->getApiDescriptionFullyQualifiedClassName($connection->identity(), $analyzer);

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
        /** @var CommandType $command */
        $command = $connection->identity();

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

        $files->add($classBuilder);
    }

    /**
     * Generates command files with corresponding value objects depending on given JSON schema metadata.
     *
     * @param VertexConnection $connection
     * @param EventSourcingAnalyzer $analyzer
     * @param FileCollection $fileCollection
     */
    public function generateCommandFile(
        VertexConnection $connection,
        EventSourcingAnalyzer $analyzer,
        FileCollection $fileCollection
    ): void {
        if ($connection->identity()->type() !== VertexType::TYPE_COMMAND) {
            throw WrongVertexConnection::forConnection($connection, VertexType::TYPE_COMMAND);
        }
        /** @var CommandType $command */
        $command = $connection->identity();
        $typeSet = $this->getMetadataTypeSetFromVertex($command);

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
