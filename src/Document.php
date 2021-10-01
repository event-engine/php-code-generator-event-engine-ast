<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst;

use EventEngine\CodeGenerator\EventEngineAst\Code\TypeDescription;
use EventEngine\CodeGenerator\EventEngineAst\Config\Naming;
use EventEngine\CodeGenerator\EventEngineAst\Exception\WrongVertexConnection;
use EventEngine\CodeGenerator\EventEngineAst\Helper\MetadataSchemaTrait;
use EventEngine\InspectioGraph\EventSourcingAnalyzer;
use EventEngine\InspectioGraph\Metadata\HasQuery;
use EventEngine\InspectioGraph\VertexConnection;
use EventEngine\InspectioGraph\VertexType;
use OpenCodeModeling\CodeAst\Builder\FileCollection;

final class Document
{
    use MetadataSchemaTrait;

    private Naming $config;
    private TypeDescription $typeDescription;
    private ValueObject $valueObject;
    private Query $query;

    public function __construct(Naming $config)
    {
        $this->config = $config;
        $this->valueObject = new ValueObject($config);
        $this->query = new Query($config);
    }

    public function generate(
        VertexConnection $connection,
        EventSourcingAnalyzer $analyzer,
        FileCollection $fileCollection
    ): void {
        if ($connection->identity()->type() !== VertexType::TYPE_DOCUMENT) {
            throw WrongVertexConnection::forConnection($connection, VertexType::TYPE_DOCUMENT);
        }
        $metadata = $connection->identity()->metadataInstance();

        if ($metadata instanceof HasQuery) {
            $this->query->generate($connection, $analyzer, $fileCollection);
        }
        $this->valueObject->generate($connection, $analyzer, $fileCollection);
    }

    public function generateApiDescription(
        VertexConnection $connection,
        EventSourcingAnalyzer $analyzer,
        FileCollection $files
    ): void {
        if ($connection->identity()->type() !== VertexType::TYPE_DOCUMENT) {
            throw WrongVertexConnection::forConnection($connection, VertexType::TYPE_DOCUMENT);
        }
        $metadata = $connection->identity()->metadataInstance();

        if ($metadata instanceof HasQuery) {
            $this->query->generateApiDescription($connection, $analyzer, $files);
            $this->query->generateApiDescriptionClassMap($connection, $analyzer, $files);
        }
        $this->valueObject->generateApiDescription($connection, $analyzer, $files);
    }

    public function generateJsonSchemaFile(
        VertexConnection $connection,
        EventSourcingAnalyzer $analyzer
    ): array {
        return \array_merge(
            $this->query->generateJsonSchemaFile($connection, $analyzer),
            $this->valueObject->generateJsonSchemaFile($connection, $analyzer)
        );
    }
}
