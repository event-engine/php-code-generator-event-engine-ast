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
use EventEngine\CodeGenerator\EventEngineAst\Exception\WrongVertexConnection;
use EventEngine\CodeGenerator\EventEngineAst\Helper\ApiDescriptionClassMapTrait;
use EventEngine\CodeGenerator\EventEngineAst\Helper\MetadataTypeSetTrait;
use EventEngine\CodeGenerator\EventEngineAst\NodeVisitor\ClassMethodDescribeCommand;
use EventEngine\InspectioGraph\CommandType;
use EventEngine\InspectioGraph\EventSourcingAnalyzer;
use EventEngine\InspectioGraph\VertexConnection;
use EventEngine\InspectioGraph\VertexType;
use OpenCodeModeling\CodeAst\Builder\FileCollection;

final class Command
{
    use MetadataTypeSetTrait;
    use ApiDescriptionClassMapTrait;

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

    public function generateJsonSchemaFile(
        VertexConnection $connection,
        EventSourcingAnalyzer $analyzer
    ): array {
        return $this->generateJsonSchemaFileFor($connection, $analyzer, VertexType::TYPE_COMMAND);
    }

    public function generateApiDescription(
        VertexConnection $connection,
        EventSourcingAnalyzer $analyzer,
        FileCollection $files
    ): void {
        $classBuilder = $this->generateApiDescriptionFor($connection, $analyzer, $files, VertexType::TYPE_COMMAND);

        $jsonSchemaFileName = '';
        $jsonSchemaRoot = '';

        if ($this->getMetadataSchemaFromVertex($connection->identity()) !== null) {
            $jsonSchemaFileName = $this->config->config()->determineSchemaFilename($connection->identity(), $analyzer);
        }

        if ($jsonSchemaFileName !== null) {
            $jsonSchemaRoot = $this->config->config()->determineSchemaRoot();
            $this->addSchemaPathConstant($classBuilder, $jsonSchemaRoot);
        }

        /** @var CommandType $command */
        $command = $connection->identity();

        $classBuilder->addNodeVisitor(
            new ClassMethodDescribeCommand(
                $this->commandDescription->generate($command, \str_replace($jsonSchemaRoot, '', $jsonSchemaFileName))
            )
        );

        $files->add($classBuilder);
    }

    public function generateApiDescriptionClassMap(
        VertexConnection $connection,
        EventSourcingAnalyzer $analyzer,
        FileCollection $files
    ): void {
        $classBuilder = $this->generateApiDescriptionClassMapFor($connection, $analyzer, $files, VertexType::TYPE_COMMAND);

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
