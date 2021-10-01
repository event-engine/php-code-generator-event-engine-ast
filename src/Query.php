<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst;

use EventEngine\CodeGenerator\EventEngineAst\Code\QueryDescription;
use EventEngine\CodeGenerator\EventEngineAst\Config\Naming;
use EventEngine\CodeGenerator\EventEngineAst\Exception\WrongVertexConnection;
use EventEngine\CodeGenerator\EventEngineAst\Helper\ApiDescriptionClassMapTrait;
use EventEngine\CodeGenerator\EventEngineAst\Helper\MetadataSchemaTrait;
use EventEngine\CodeGenerator\EventEngineAst\Helper\MetadataTypeSetTrait;
use EventEngine\CodeGenerator\EventEngineAst\Metadata\HasTypeSet;
use EventEngine\CodeGenerator\EventEngineAst\NodeVisitor\ClassMethodDescribeQuery;
use EventEngine\InspectioGraph\DocumentType;
use EventEngine\InspectioGraph\EventSourcingAnalyzer;
use EventEngine\InspectioGraph\VertexConnection;
use EventEngine\InspectioGraph\VertexType;
use OpenCodeModeling\CodeAst\Builder\ClassBuilder;
use OpenCodeModeling\CodeAst\Builder\ClassMethodBuilder;
use OpenCodeModeling\CodeAst\Builder\FileCollection;
use OpenCodeModeling\CodeAst\Builder\ParameterBuilder;

final class Query
{
    use MetadataTypeSetTrait;
    use MetadataSchemaTrait;
    use ApiDescriptionClassMapTrait;

    private Naming $config;
    private QueryDescription $queryDescription;

    public function __construct(Naming $config)
    {
        $this->config = $config;
        $this->queryDescription = new QueryDescription(
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
        if (! $this->isQuery($connection)) {
            return;
        }

        /** @var DocumentType $document */
        $document = $connection->identity();

        $this->generateResolver($document, $analyzer, $fileCollection);
        $this->generateQuery($document, $analyzer, $fileCollection);
    }

    private function generateResolver(
        DocumentType $document,
        EventSourcingAnalyzer $analyzer,
        FileCollection $fileCollection
    ): void {
        $queryFqcn = $this->config->getQueryFullyQualifiedClassName($document, $analyzer);
        $resolverFqcn = $this->config->getResolverFullyQualifiedClassName($document, $analyzer);
        $valueObjectFqcn = $this->config->getFullyQualifiedClassName($document, $analyzer);

        $resolverClassBuilder = ClassBuilder::fromScratch(
            $this->config->getClassNameFromFullyQualifiedClassName($resolverFqcn),
            $this->config->getClassNamespaceFromFullyQualifiedClassName($resolverFqcn)
        );

        $resolverClassBuilder->setFinal(true)
            ->setNamespaceImports(
                'EventEngine\Messaging\Message',
                'EventEngine\Querying\Resolver',
                $valueObjectFqcn,
                $queryFqcn
            )
            ->setImplements('Resolver');

        $resolveMethodBuilder = ClassMethodBuilder::fromScratch('resolve')
            ->setParameters(
                ParameterBuilder::fromScratch(
                    'query',
                    'Message',
                )
            );
        $resolveMethodBuilder->setReturnType(
            $this->config->getClassNameFromFullyQualifiedClassName($valueObjectFqcn),
        );

        $resolverClassBuilder->addMethod($resolveMethodBuilder);

        $fileCollection->add($resolverClassBuilder);
    }

    private function generateQuery(
        DocumentType $document,
        EventSourcingAnalyzer $analyzer,
        FileCollection $fileCollection
    ): void {
        $queryFqcn = $this->config->getQueryFullyQualifiedClassName($document, $analyzer);

        $files = $this->config->config()->getObjectGenerator()->generateImmutableRecord(
            $queryFqcn,
            $this->config->config()->determineValueObjectPath($document, $analyzer),
            $this->config->config()->determineValueObjectSharedPath(),
            $this->getMetadataQueryTypeSetFromVertex($document)
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
        if ($connection->identity()->type() !== VertexType::TYPE_DOCUMENT) {
            throw WrongVertexConnection::forConnection($connection, VertexType::TYPE_DOCUMENT);
        }
        if (! $this->isQuery($connection)) {
            return;
        }

        $classBuilder = $this->generateApiQueryDescriptionFor($connection, $analyzer, $files);

        $jsonSchemaFileName = '';
        $jsonSchemaRoot = '';

        /** @var DocumentType $document */
        $document = $connection->identity();

        $queryMetadata = $this->getMetadataQueryTypeSetFromVertex($document);

        if ($queryMetadata) {
            $jsonSchemaFileName = $this->config->config()->determineSchemaFilename($connection->identity(), $analyzer);

            if ($jsonSchemaFileName !== null) {
                $jsonSchemaRoot = $this->config->config()->determineSchemaRoot();
                $this->addSchemaPathConstant($classBuilder, $jsonSchemaRoot);
            }
        }

        $resolverFqcn = $this->config->getResolverFullyQualifiedClassName($document, $analyzer);

        $namespace = '';

        if ($queryMetadata) {
            $namespace = $this->getNamespace($queryMetadata->first());
        } else {
            $metadata = $document->metadataInstance();

            if ($metadata instanceof HasTypeSet
                && $metadata->typeSet() !== null
            ) {
                $namespace = $this->getNamespace($metadata->typeSet()->first());
            }
        }

        $typeRef = \sprintf('new TypeRef(Type::%s)', ($this->config->config()->getFilterConstName())($namespace . $document->label()));

        $classBuilder->addNodeVisitor(
            new ClassMethodDescribeQuery(
                $this->queryDescription->generate(
                    $document,
                    $this->config->getClassNameFromFullyQualifiedClassName($resolverFqcn),
                    $typeRef,
                    \str_replace(
                        [$jsonSchemaRoot, DIRECTORY_SEPARATOR . 'ValueObject' . DIRECTORY_SEPARATOR],
                        ['', DIRECTORY_SEPARATOR . 'Query' . DIRECTORY_SEPARATOR],
                        $jsonSchemaFileName
                    ),
                )
            )
        );

        $files->add($classBuilder);
    }

    public function generateApiDescriptionClassMap(
        VertexConnection $connection,
        EventSourcingAnalyzer $analyzer,
        FileCollection $files
    ): void {
        if (! $this->isQuery($connection)) {
            return;
        }
        $classBuilder = $this->generateApiQueryDescriptionClassMapFor($connection, $analyzer, $files);

        $files->add($classBuilder);
    }

    public function generateJsonSchemaFile(
        VertexConnection $connection,
        EventSourcingAnalyzer $analyzer
    ): array {
        if (! $this->isQuery($connection)) {
            return [];
        }

        return $this->generateJsonSchemaFileFor($connection, $analyzer, VertexType::TYPE_DOCUMENT);
    }

    private function isQuery(VertexConnection $connection): bool
    {
        if ($connection->identity()->type() !== VertexType::TYPE_DOCUMENT) {
            return false;
        }
        $metadataSchema = $this->getMetadataQueryTypeSetFromVertex($connection->identity());

        return $metadataSchema !== null;
    }
}
