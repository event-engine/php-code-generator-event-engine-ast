<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Helper;

use EventEngine\CodeGenerator\EventEngineAst\Code\DescriptionFileMethod;
use EventEngine\CodeGenerator\EventEngineAst\Config\Naming;
use EventEngine\CodeGenerator\EventEngineAst\Exception\WrongVertexConnection;
use EventEngine\CodeGenerator\EventEngineAst\Metadata\HasTypeSet;
use EventEngine\CodeGenerator\EventEngineAst\NodeVisitor\ClassMap;
use EventEngine\InspectioGraph\AggregateType;
use EventEngine\InspectioGraph\DocumentType;
use EventEngine\InspectioGraph\EventSourcingAnalyzer;
use EventEngine\InspectioGraph\Metadata\HasQuery;
use EventEngine\InspectioGraph\Metadata\HasSchema;
use EventEngine\InspectioGraph\VertexConnection;
use EventEngine\InspectioGraph\VertexType;
use OpenCodeModeling\CodeAst\Builder\ClassBuilder;
use OpenCodeModeling\CodeAst\Builder\ClassConstBuilder;
use OpenCodeModeling\CodeAst\Builder\ClassMethodBuilder;
use OpenCodeModeling\CodeAst\Builder\File;
use OpenCodeModeling\CodeAst\Builder\FileCollection;
use OpenCodeModeling\CodeAst\Builder\PhpFile;
use OpenCodeModeling\JsonSchemaToPhp\Type\TypeDefinition;

trait ApiDescriptionClassMapTrait
{
    use MetadataSchemaTrait;
    use MetadataCustomTrait;

    private Naming $config;

    private function generateApiDescriptionFor(
        VertexConnection $connection,
        EventSourcingAnalyzer $analyzer,
        FileCollection $files,
        string $type
    ): ClassBuilder {
        $classBuilder = $this->getApiDescriptionClassBuilder($connection, $analyzer, $files, $type);

        $this->addDescriptionMethod($classBuilder, $connection);

        return $classBuilder;
    }

    private function generateApiQueryDescriptionFor(
        VertexConnection $connection,
        EventSourcingAnalyzer $analyzer,
        FileCollection $files
    ): ClassBuilder {
        $classBuilder = $this->getApiQueryDescriptionClassBuilder($connection, $analyzer, $files);

        $this->addDescriptionMethod($classBuilder, $connection);

        return $classBuilder;
    }

    private function addDescriptionMethod(ClassBuilder $classBuilder, VertexConnection $connection): void
    {
        $classBuilder->addMethod(
            ClassMethodBuilder::fromNode(
                DescriptionFileMethod::generate()->generate(),
                true,
                $this->config->config()->getPrinter()
            )
        );

        $namespace = '';

        $identity = $connection->identity();

        $metadata = $identity->metadataInstance();

        if ($metadata instanceof HasTypeSet
            && $metadata->typeSet() !== null
        ) {
            $namespace = $this->getNamespace($metadata->typeSet()->first());
        }

        $classBuilder->addConstant(
            ClassConstBuilder::fromScratch(
                ($this->config->config()->getFilterConstName())($namespace . $identity->label()),
                $namespace . ($this->config->config()->getFilterMessageName())($identity->label()),
            )
        );
    }

    private function getNamespace(TypeDefinition $typeDefinition): string
    {
        $namespace = $this->getCustomMetadataFromTypeDefinition(
            $typeDefinition,
            'ns',
            $this->getCustomMetadataFromTypeDefinition($typeDefinition, 'namespace', '')
        );

        if ($namespace !== '') {
            $namespace = \trim($namespace, '/') . '/';
        }

        if ($namespace === '/') {
            return '';
        }

        return $namespace;
    }

    private function addSchemaPathConstant(ClassBuilder $classBuilder, string $schemaPath): void
    {
        $classInfo = $this->config->config()->getClassInfoList()->classInfoForPath($schemaPath);

        $classBuilder->addConstant(
            ClassConstBuilder::fromScratch(
                'SCHEMA_PATH',
                \trim(\str_replace($classInfo->getSourceFolder(), 'src', $schemaPath), DIRECTORY_SEPARATOR)
            )->setPrivate()
        );
    }

    private function generateApiDescriptionClassMapFor(
        VertexConnection $connection,
        EventSourcingAnalyzer $analyzer,
        FileCollection $files,
        string $type
    ): ClassBuilder {
        $classBuilder = $this->getApiDescriptionClassBuilder($connection, $analyzer, $files, $type);

        $identity = $connection->identity();

        switch (true) {
            case $identity instanceof AggregateType:
                $identityFqcn = $this->config->getAggregateBehaviourFullyQualifiedClassName($identity, $analyzer);
                break;
            default:
                $identityFqcn = $this->config->getFullyQualifiedClassName($identity, $analyzer);
                break;
        }

        $metadata = $identity->metadataInstance();

        $namespace = '';

        if ($metadata instanceof HasTypeSet
            && $metadata->typeSet() !== null
        ) {
            $namespace = $this->getNamespace($metadata->typeSet()->first());
        }

        $this->addClassMap($classBuilder, $identityFqcn, $namespace . $identity->label());

        return $classBuilder;
    }

    private function generateApiQueryDescriptionClassMapFor(
        VertexConnection $connection,
        EventSourcingAnalyzer $analyzer,
        FileCollection $files
    ): ClassBuilder {
        $classBuilder = $this->getApiQueryDescriptionClassBuilder($connection, $analyzer, $files);

        /** @var DocumentType $identity */
        $identity = $connection->identity();

        $identityFqcn = $this->config->getQueryFullyQualifiedClassName($identity, $analyzer);

        $metadata = $identity->metadataInstance();

        $namespace = '';

        if ($metadata instanceof HasTypeSet
            && $metadata->typeSet() !== null
        ) {
            $namespace = $this->getNamespace($metadata->typeSet()->first());
        }

        $this->addClassMap($classBuilder, $identityFqcn, $namespace . $identity->label());

        $classBuilder->addNamespaceImport($this->config->getResolverFullyQualifiedClassName($identity, $analyzer));

        return $classBuilder;
    }

    private function addClassMap(ClassBuilder $classBuilder, string $identityFqcn, string $identityLabel): void
    {
        $classBuilder->addNamespaceImport(
            $identityFqcn
        );

        $classBuilder->addConstant(
            ClassConstBuilder::fromScratch('CLASS_MAP', [])
        );

        $classBuilder->addNodeVisitor(
            new ClassMap(
                ($this->config->config()->getFilterConstName())($identityLabel),
                $this->config->getClassNameFromFullyQualifiedClassName($identityFqcn)
            )
        );
    }

    private function getApiDescriptionClassBuilder(
        VertexConnection $connection,
        EventSourcingAnalyzer $analyzer,
        FileCollection $files,
        string $type
    ): ClassBuilder {
        if ($connection->identity()->type() !== $type) {
            throw WrongVertexConnection::forConnection($connection, $type);
        }
        $apiFqcn = $this->config->getApiDescriptionFullyQualifiedClassName($connection->identity(), $analyzer);

        return $this->getClassBuilder($apiFqcn, $files);
    }

    private function getApiQueryDescriptionClassBuilder(
        VertexConnection $connection,
        EventSourcingAnalyzer $analyzer,
        FileCollection $files
    ): ClassBuilder {
        if ($connection->identity()->type() !== VertexType::TYPE_DOCUMENT) {
            throw WrongVertexConnection::forConnection($connection, VertexType::TYPE_DOCUMENT);
        }
        /** @var DocumentType $identity */
        $identity = $connection->identity();

        $apiFqcn = $this->config->getApiQueryDescriptionFullyQualifiedClassName($identity, $analyzer);

        return $this->getClassBuilder($apiFqcn, $files);
    }

    private function getClassBuilder(string $apiFqcn, FileCollection $files): ClassBuilder
    {
        $classNamespace = $this->config->getClassNamespaceFromFullyQualifiedClassName($apiFqcn);
        $className = $this->config->getClassNameFromFullyQualifiedClassName($apiFqcn);

        $classBuilderFile = $files->filter(
            fn (File $file
            ) => $file instanceof PhpFile && $file->getNamespace() === $classNamespace && $file->getName() === $className
        );

        if ($classBuilderFile->valid() && $classBuilderFile->current() instanceof ClassBuilder) {
            return $classBuilderFile->current();
        }

        $classBuilder = ClassBuilder::fromScratch(
            $className,
            $classNamespace
        )->setFinal(true);

        $classBuilder->addNamespaceImport(
            'EventEngine\EventEngine',
            'EventEngine\EventEngineDescription',
            'EventEngine\JsonSchema\JsonSchemaArray'
        );

        $classBuilder->addImplement('EventEngineDescription');

        return $classBuilder;
    }

    private function generateJsonSchemaFileFor(
        VertexConnection $connection,
        EventSourcingAnalyzer $analyzer,
        string $type
    ): array {
        if ($connection->identity()->type() !== $type) {
            throw WrongVertexConnection::forConnection($connection, $type);
        }
        $identity = $connection->identity();

        $files = [];

        if ($identity->metadataInstance() instanceof HasQuery) {
            $schema = $this->getMetadataQuerySchemaFromVertex($identity);
            if ($schema !== null) {
                $filename = $this->config->config()->determineQuerySchemaFilename($identity, $analyzer);

                $files[$filename] = [
                    'filename' => $filename,
                    'code' => \json_encode($schema, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT),
                ];
            }
        }
        if ($identity->metadataInstance() instanceof HasSchema) {
            $schema = $this->getMetadataSchemaFromVertex($identity);
            if ($schema !== null) {
                $filename = $this->config->config()->determineSchemaFilename($identity, $analyzer);
                $files[$filename] = [
                    'filename' => $filename,
                    'code' => \json_encode($schema, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT),
                ];
            }
        }

        return $files;
    }
}
