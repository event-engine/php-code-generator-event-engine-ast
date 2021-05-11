<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Config;

use EventEngine\CodeGenerator\EventEngineAst\Exception\RuntimeException;
use EventEngine\CodeGenerator\EventEngineAst\Metadata\AggregateMetadata;
use EventEngine\InspectioGraph\AggregateType;
use EventEngine\InspectioGraph\CommandType;
use EventEngine\InspectioGraph\EventSourcingAnalyzer;
use EventEngine\InspectioGraph\EventType;
use EventEngine\InspectioGraph\VertexType;
use OpenCodeModeling\Filter\FilterFactory;

final class PreConfiguredNaming implements Naming
{
    use FilterAggregateIdNameTrait;
    use FilterAggregateStoreStateInTrait;
    use FilterCommandMethodNameTrait;
    use FilterEventMethodNameTrait;
    use FilterWithMethodNameTrait;

    private Base $config;

    public function __construct(Base $config)
    {
        $this->config = $config;

        $this->setFilterCommandMethodName(FilterFactory::methodNameFilter());
        $this->injectFilterEventMethodName(FilterFactory::methodNameFilter());
        $this->injectFilterAggregateIdName(FilterFactory::propertyNameFilter());
        $this->injectFilterWithMethodName(FilterFactory::methodNameFilter());
        $this->injectFilterAggregateStoreStateIn($this->config->getFilterConstValue());
    }

    public function getAggregateStateFullyQualifiedClassName(
        AggregateType $type,
        EventSourcingAnalyzer $analyzer
    ): string {
        $namespace = $this->getClassNamespaceFromPath(
            $this->config->determinePath($type, $analyzer)
        );

        $className = ($this->config->getFilterClassName())($type->name());

        return $namespace . '\\' . $className . 'State';
    }

    public function getAggregateBehaviourFullyQualifiedClassName(
        AggregateType $type,
        EventSourcingAnalyzer $analyzer
    ): string {
        $namespace = $this->getClassNamespaceFromPath(
            $this->config->determinePath($type, $analyzer)
        );

        $className = ($this->config->getFilterClassName())($type->name());

        return $namespace . '\\' . $className;
    }

    public function getAggregateIdFullyQualifiedClassName(AggregateType $type, EventSourcingAnalyzer $analyzer): string
    {
        $aggregateMetadata = $type->metadataInstance();

        if (! $aggregateMetadata instanceof AggregateMetadata) {
            throw new RuntimeException(
                \sprintf(
                    'Cannot generate aggregate "%s". Need metadata of type %s',
                    $type->name(),
                    AggregateMetadata::class
                )
            );
        }

        $identifier = $aggregateMetadata->identifier();

        $namespace = $this->getClassNamespaceFromPath(
            $this->config->determineValueObjectSharedPath()
        );
        $voName = ($this->config->getFilterClassName())($identifier);

        return $namespace . '\\' . $voName;
    }

    public function getFullyQualifiedClassName(VertexType $type, EventSourcingAnalyzer $analyzer): string
    {
        return $this->getClassNamespaceFromPath(
                $this->config->determinePath($type, $analyzer)
            ) . '\\' . ($this->config->getFilterClassName())($type->name());
    }

    public function getMessageName(VertexType $type, EventSourcingAnalyzer $analyzer): string
    {
        return ($this->config->getFilterClassName())($type->name());
    }

    public function getClassNamespaceFromPath(string $path): string
    {
        return $this->config->getClassInfoList()
            ->classInfoForPath($path)
            ->getClassNamespaceFromPath($path);
    }

    public function getFullyQualifiedClassNameFromFilename(string $filename): string
    {
        return $this->config->getClassInfoList()
            ->classInfoForPath($filename)
            ->getFullyQualifiedClassNameFromFilename($filename);
    }

    public function getClassNameFromFullyQualifiedClassName(string $fqcn): string
    {
        return $this->config->getClassInfoList()->classInfoForNamespace($fqcn)->getClassName($fqcn);
    }

    public function getClassNamespaceFromFullyQualifiedClassName(string $fqcn): string
    {
        return $this->config->getClassInfoList()->classInfoForNamespace($fqcn)->getClassNamespace($fqcn);
    }

    public function getFullPathFromFullyQualifiedClassName(string $fqcn): string
    {
        $classInfo = $this->config->getClassInfoList()->classInfoForNamespace($fqcn);

        return $classInfo->getSourceFolder() . DIRECTORY_SEPARATOR . $classInfo->getPath($fqcn);
    }

    public function getAggregateBehaviourCommandHandlingMethodName(
        CommandType $type,
        EventSourcingAnalyzer $analyzer
    ): string {
        return ($this->config->getFilterMethodName())($type->name());
    }

    public function getAggregateBehaviourEventHandlingMethodName(
        EventType $type,
        EventSourcingAnalyzer $analyzer
    ): string {
        return ($this->config->getFilterMethodName())($type->name());
    }

    public function config(): Base
    {
        return $this->config;
    }
}