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
use EventEngine\CodeGenerator\EventEngineAst\NodeVisitor\ClassMap;
use EventEngine\InspectioGraph\AggregateType;
use EventEngine\InspectioGraph\EventSourcingAnalyzer;
use EventEngine\InspectioGraph\VertexConnection;
use OpenCodeModeling\CodeAst\Builder\ClassBuilder;
use OpenCodeModeling\CodeAst\Builder\ClassConstBuilder;
use OpenCodeModeling\CodeAst\Builder\ClassMethodBuilder;
use OpenCodeModeling\CodeAst\Builder\File;
use OpenCodeModeling\CodeAst\Builder\FileCollection;
use OpenCodeModeling\CodeAst\Builder\PhpFile;

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

        $classBuilder->addMethod(
            ClassMethodBuilder::fromNode(
                DescriptionFileMethod::generate()->generate(),
                true,
                $this->config->config()->getPrinter()
            )
        );

        $identity = $connection->identity();
        $namespace = $this->getCustomMetadata($identity, 'ns', $this->getCustomMetadata($identity, 'namespace', ''));

        if ($namespace !== '') {
            $namespace = \trim($namespace, '/') . '/';
        }

        $classBuilder->addConstant(
            ClassConstBuilder::fromScratch(
                ($this->config->config()->getFilterConstName())($namespace . $identity->label()),
                $namespace . ($this->config->config()->getFilterMessageName())($identity->label()),
            )
        );

        return $classBuilder;
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

        if ($identity instanceof AggregateType) {
            $identityFqcn = $this->config->getAggregateBehaviourFullyQualifiedClassName($identity, $analyzer);
        } else {
            $identityFqcn = $this->config->getFullyQualifiedClassName($identity, $analyzer);
        }

        $classBuilder->addNamespaceImport(
            $identityFqcn
        );

        $classBuilder->addConstant(
            ClassConstBuilder::fromScratch('CLASS_MAP', [])
        );

        $classBuilder->addNodeVisitor(
            new ClassMap(
                ($this->config->config()->getFilterConstName())($identity->label()),
                $this->config->getClassNameFromFullyQualifiedClassName($identityFqcn)
            )
        );

        return $classBuilder;
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
        $schema = $this->getMetadataSchemaFromVertex($identity);

        if ($schema === null) {
            return [];
        }

        $filename = $this->config->config()->determineSchemaFilename($identity, $analyzer);

        return [
            $filename => [
                'filename' => $filename,
                'code' => \json_encode($schema, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT),
            ],
        ];
    }
}
