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
use EventEngine\CodeGenerator\EventEngineAst\Helper\ApiDescriptionClassMapTrait;
use EventEngine\CodeGenerator\EventEngineAst\Helper\MetadataSchemaTrait;
use EventEngine\CodeGenerator\EventEngineAst\Helper\MetadataTypeSetTrait;
use EventEngine\CodeGenerator\EventEngineAst\NodeVisitor\ClassMethodDescribeType;
use EventEngine\InspectioGraph\DocumentType;
use EventEngine\InspectioGraph\EventSourcingAnalyzer;
use EventEngine\InspectioGraph\VertexConnection;
use EventEngine\InspectioGraph\VertexType;
use OpenCodeModeling\CodeAst\Builder\FileCollection;

final class ValueObject
{
    use MetadataTypeSetTrait;
    use MetadataSchemaTrait;
    use ApiDescriptionClassMapTrait;

    private Naming $config;
    private TypeDescription $typeDescription;

    public function __construct(Naming $config)
    {
        $this->config = $config;
        $this->typeDescription = new TypeDescription(
            $this->config->config()->getParser(),
            $this->config->config()->getFilterConstName()
        );
    }

    public function generate(
        VertexConnection $connection,
        EventSourcingAnalyzer $analyzer,
        FileCollection $fileCollection
    ): void {
        if ($connection->identity()->type() !== VertexType::TYPE_DOCUMENT) {
            throw WrongVertexConnection::forConnection($connection, VertexType::TYPE_DOCUMENT);
        }

        /** @var DocumentType $document */
        $document = $connection->identity();

        $documentFqcn = $this->config->getFullyQualifiedClassName($document, $analyzer);

        $files = $this->config->config()->getObjectGenerator()->generateImmutableRecord(
            $documentFqcn,
            $this->config->config()->determineValueObjectPath($document, $analyzer),
            $this->config->config()->determineValueObjectSharedPath(),
            $this->getMetadataTypeSetFromVertex($document)
        );

        foreach ($files as $item) {
            $fileCollection->add($item);
        }
    }

    public function generateApiDescription(
        VertexConnection $connection,
        EventSourcingAnalyzer $analyzer,
        FileCollection $files
    ): void {
        $classBuilder = $this->generateApiDescriptionFor($connection, $analyzer, $files, VertexType::TYPE_DOCUMENT);

        $jsonSchemaFileName = '';
        $jsonSchemaRoot = '';

        if ($this->getMetadataSchemaFromVertex($connection->identity()) !== null) {
            $jsonSchemaFileName = $this->config->config()->determineSchemaFilename($connection->identity(), $analyzer);
        }

        if ($jsonSchemaFileName !== null) {
            $jsonSchemaRoot = $this->config->config()->determineSchemaRoot();
            $this->addSchemaPathConstant($classBuilder, $jsonSchemaRoot);
        }

        /** @var DocumentType $document */
        $document = $connection->identity();

        $classBuilder->addNodeVisitor(
            new ClassMethodDescribeType(
                $this->typeDescription->generate($document, \str_replace($jsonSchemaRoot, '', $jsonSchemaFileName))
            )
        );

        $files->add($classBuilder);
    }

    public function generateJsonSchemaFile(
        VertexConnection $connection,
        EventSourcingAnalyzer $analyzer
    ): array {
        return $this->generateJsonSchemaFileFor($connection, $analyzer, VertexType::TYPE_DOCUMENT);
    }
}
