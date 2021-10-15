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
use OpenCodeModeling\CodeAst\Builder\ClassPropertyBuilder;
use OpenCodeModeling\CodeAst\Builder\File;
use OpenCodeModeling\CodeAst\Builder\FileCollection;
use OpenCodeModeling\CodeAst\Builder\ParameterBuilder;
use OpenCodeModeling\CodeAst\Builder\PhpFile;

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

        $this->generateQuery($document, $analyzer, $fileCollection);
        $this->generateResolver($document, $analyzer, $fileCollection);
        $this->generateFinder($document, $analyzer, $fileCollection);
    }

    private function generateResolver(
        DocumentType $document,
        EventSourcingAnalyzer $analyzer,
        FileCollection $fileCollection
    ): void {
        $queryFqcn = $this->config->getQueryFullyQualifiedClassName($document, $analyzer);
        $resolverFqcn = $this->config->getResolverFullyQualifiedClassName($document, $analyzer);
        $finderFqcn = $this->config->getFinderFullyQualifiedClassName($document, $analyzer);
        $valueObjectFqcn = $this->config->getFullyQualifiedClassName($document, $analyzer);

        $resolverClassBuilder = ClassBuilder::fromScratch(
            $this->config->getClassNameFromFullyQualifiedClassName($resolverFqcn),
            $this->config->getClassNamespaceFromFullyQualifiedClassName($resolverFqcn)
        );

        $resolverClassBuilder->setFinal(true)
            ->setNamespaceImports(
                'EventEngine\Messaging\Message',
                'EventEngine\Querying\Resolver',
                $finderFqcn,
                $valueObjectFqcn,
                $queryFqcn
            )
            ->setImplements('Resolver');

        $finderClassName = $this->config->getClassNameFromFullyQualifiedClassName($finderFqcn);
        $resolverClassBuilder->addProperty(ClassPropertyBuilder::fromScratch('finder', $finderClassName));

        $resolverConstructMethod = ClassMethodBuilder::fromScratch('__construct');
        $resolverConstructMethod->setParameters(
                ParameterBuilder::fromScratch('finder', $finderClassName)
            )
            ->setBody('$this->finder = $finder;');

        $resolverClassBuilder->addMethod($resolverConstructMethod);

        $resolveMethodBuilder = ClassMethodBuilder::fromScratch('resolve')
            ->setParameters(
                ParameterBuilder::fromScratch(
                    'query',
                    'Message',
                )
            );

        $queryClassNamespace = $this->config->getClassNamespaceFromFullyQualifiedClassName($queryFqcn);
        $queryClassName = $this->config->getClassNameFromFullyQualifiedClassName($queryFqcn);

        $queryClassBuilder = $this->searchForClassBuilder($fileCollection, $queryClassNamespace, $queryClassName);

        $finderMethodArgs = '';

        if ($queryClassBuilder) {
            foreach ($queryClassBuilder->getMethods() as $method) {
                $finderMethodArgs .= '$findBy->' . $method->getName() . '(),';
            }
        }
        $finderMethodArgs = \trim($finderMethodArgs, ',');

        $valueObjectClassName = $this->config->getClassNameFromFullyQualifiedClassName($valueObjectFqcn);
        $finderMethodName = 'find' . $valueObjectClassName;

        $queryClassName = $this->config->getClassNameFromFullyQualifiedClassName($queryFqcn);
        $resolveMethodBuilder->setBody(
            \sprintf(
                <<<'EOF'
                /** @var %s $findBy */
                $findBy = %s::fromArray($query->payload());
                return $this->finder->%s(%s);
                EOF,
                $queryClassName,
                $queryClassName,
                $finderMethodName,
                $finderMethodArgs
            )
        );

        $resolveMethodBuilder->setReturnType($valueObjectClassName);

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

    private function generateFinder(
        DocumentType $document,
        EventSourcingAnalyzer $analyzer,
        FileCollection $fileCollection
    ): void {
        $queryFqcn = $this->config->getQueryFullyQualifiedClassName($document, $analyzer);
        $finderFqcn = $this->config->getFinderFullyQualifiedClassName($document, $analyzer);
        $valueObjectFqcn = $this->config->getFullyQualifiedClassName($document, $analyzer);

        $finderClassBuilder = ClassBuilder::fromScratch(
            $this->config->getClassNameFromFullyQualifiedClassName($finderFqcn),
            $this->config->getClassNamespaceFromFullyQualifiedClassName($finderFqcn)
        );

        $finderClassBuilder->setFinal(true)
            ->setNamespaceImports(
                'EventEngine\DocumentStore\DocumentStore',
            );

        $finderClassBuilder->addProperty(ClassPropertyBuilder::fromScratch('documentStore', 'DocumentStore'));

        $finderConstructMethod = ClassMethodBuilder::fromScratch('__construct');
        $finderConstructMethod->setParameters(ParameterBuilder::fromScratch('documentStore', 'DocumentStore'))
            ->setBody('$this->documentStore = $documentStore;');

        $finderClassBuilder->addMethod($finderConstructMethod);

        $queryClassNamespace = $this->config->getClassNamespaceFromFullyQualifiedClassName($queryFqcn);
        $queryClassName = $this->config->getClassNameFromFullyQualifiedClassName($queryFqcn);

        $queryClassBuilder = $this->searchForClassBuilder($fileCollection, $queryClassNamespace, $queryClassName);

        if ($queryClassBuilder) {
            $findMethod = ClassMethodBuilder::fromScratch(
                ($this->config->config()->getFilterMethodName())('find_' . $document->label())
            );

            $parameters = [];
            $nsImports = $queryClassBuilder->getNamespaceImports();

            foreach ($queryClassBuilder->getProperties() as $property) {
                $nsImport = \array_filter($nsImports, static fn (string $nsImport) => \strpos($nsImport, $property->getType()) !== false);

                if (! empty($nsImport)) {
                    $finderClassBuilder->addNamespaceImport(\current($nsImport));
                }

                $parameters[] = ParameterBuilder::fromScratch($property->getName(), $property->getType());
            }
            $voClassName = $this->config->getClassNameFromFullyQualifiedClassName($valueObjectFqcn);
            $findMethod->setParameters(...$parameters);
            $findMethod->setReturnType($voClassName);

            $findMethod->setBody(
                \sprintf(
                    <<<'EOF'
                    // TODO Cody here, I need your help. Please implement the missing lines.
                    $doc = $this->documentStore;
                    return %s::fromArray($doc['state']);
                    EOF,
                    $voClassName
                )
            );
            $finderClassBuilder->addNamespaceImport($valueObjectFqcn);
            $finderClassBuilder->addMethod($findMethod);
        }

        $fileCollection->add($finderClassBuilder);
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

        $classBuilder->addNamespaceImport('EventEngine\JsonSchema\Type\TypeRef');

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

    private function searchForClassBuilder(FileCollection $fileCollection, string $classNamespace, string $className): ?ClassBuilder
    {
        $classBuilderFile = $fileCollection->filter(
            fn (File $file) => $file instanceof PhpFile && $file->getNamespace() === $classNamespace && $file->getName() === $className
        );

        if ($classBuilderFile->valid() && $classBuilderFile->current() instanceof ClassBuilder) {
            return $classBuilderFile->current();
        }

        return null;
    }
}
