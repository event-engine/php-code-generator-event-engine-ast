<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Config;

use EventEngine\CodeGenerator\EventEngineAst\Exception\RuntimeException;
use EventEngine\CodeGenerator\EventEngineAst\Metadata\HasTypeSet;
use EventEngine\InspectioGraph\AggregateType;
use EventEngine\InspectioGraph\CommandType;
use EventEngine\InspectioGraph\Connection\AggregateConnectionAnalyzer;
use EventEngine\InspectioGraph\Connection\FeatureConnectionAnalyzer;
use EventEngine\InspectioGraph\DocumentType;
use EventEngine\InspectioGraph\EventSourcingAnalyzer;
use EventEngine\InspectioGraph\EventType;
use EventEngine\InspectioGraph\FeatureType;
use EventEngine\InspectioGraph\Metadata\HasCustomData;
use EventEngine\InspectioGraph\VertexType;
use OpenCodeModeling\JsonSchemaToPhp\Type\CustomSupport;

trait DeterminePathTrait
{
    abstract public function getBasePath(): string;

    abstract public function getFilterClassName(): callable;

    public function determineValueObjectPath(VertexType $type, EventSourcingAnalyzer $analyzer): string
    {
        switch (true) {
            case $type instanceof CommandType:
            case $type instanceof EventType:
            case $type instanceof AggregateType:
            case $type instanceof DocumentType:
                return $this->determineDomainPath($type, $analyzer) . DIRECTORY_SEPARATOR . 'ValueObject';
            default:
                return $this->determineValueObjectSharedPath();
        }
    }

    public function determineValueObjectSharedPath(): string
    {
        return $this->getBasePath() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Domain' . DIRECTORY_SEPARATOR . 'Model' . DIRECTORY_SEPARATOR . 'ValueObject';
    }

    public function determineApplicationPath(VertexType $type, EventSourcingAnalyzer $analyzer): string
    {
        $filterFolder = $this->getFilterClassName();

        $path = $this->getBasePath() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Application';

        $feature = $this->determineFeature($type, $analyzer);

        if ($feature !== null) {
            $folder = $feature->label();

            if (($metadata = $feature->metadataInstance())
                && $metadata instanceof HasCustomData
            ) {
                $folder = $metadata->customData()['name'] ?? $folder;
            }
            $path .= DIRECTORY_SEPARATOR . ($filterFolder)($folder);
        }

        return $path;
    }

    public function determineApplicationRoot(): string
    {
        return $this->getBasePath() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Application';
    }

    public function determineInfrastructurePath(VertexType $type, EventSourcingAnalyzer $analyzer): string
    {
        $filterFolder = $this->getFilterClassName();

        $path = $this->determineInfrastructureRoot();

        $aggregate = $this->determineAggregate($type, $analyzer);

        if ($aggregate !== null) {
            $path .= DIRECTORY_SEPARATOR . ($filterFolder)($aggregate->label());
        }

        return $path;
    }

    public function determineInfrastructureRoot(): string
    {
        return $this->getBasePath() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Infrastructure';
    }

    public function determineDomainPath(VertexType $type, EventSourcingAnalyzer $analyzer): string
    {
        $filterFolder = $this->getFilterClassName();

        $path = $this->determineDomainRoot() . DIRECTORY_SEPARATOR . 'Model';

        $aggregate = $this->determineAggregate($type, $analyzer);

        if ($aggregate !== null) {
            $path .= DIRECTORY_SEPARATOR . ($filterFolder)($aggregate->label());
        }

        return $path;
    }

    public function determineDomainRoot(): string
    {
        return $this->getBasePath() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Domain';
    }

    public function determinePath(VertexType $type, EventSourcingAnalyzer $analyzer): string
    {
        $namespace = $this->getCustomMetadata($type, 'namespace') ?? '';

        if ($namespace === '') {
            $namespace = $this->getCustomMetadata($type, 'ns') ?? '';
        }

        $namespace = DIRECTORY_SEPARATOR . \str_replace('\\', '//', \trim($namespace, '\\'));

        switch (true) {
            case $type instanceof CommandType:
                return $this->determineDomainPath($type, $analyzer) . DIRECTORY_SEPARATOR . 'Command' . $namespace;
            case $type instanceof EventType:
                return $this->determineDomainPath($type, $analyzer) . DIRECTORY_SEPARATOR . 'Event' . $namespace;
            case $type instanceof AggregateType:
                return $this->determineDomainPath($type, $analyzer) . DIRECTORY_SEPARATOR . $namespace;
            case $type instanceof DocumentType:
                return $this->determineDomainPath($type, $analyzer) . DIRECTORY_SEPARATOR . 'ValueObject' . $namespace;
            default:
                throw new RuntimeException(
                    \sprintf('Can not determine path for sticky type "%s"', \get_class($type))
                );
        }
    }

    private function getCustomMetadata(VertexType $type, string $key)
    {
        $metadataInstance = $type->metadataInstance();

        if (
            $metadataInstance instanceof HasTypeSet
            && ($jsonSchemaTypeSet = $metadataInstance->typeSet())
            && ($jsonSchemaType = $jsonSchemaTypeSet->first())
            && $jsonSchemaType instanceof CustomSupport
        ) {
            return $jsonSchemaType->custom()[$key] ?? null;
        }

        return null;
    }

    public function determineFilename(VertexType $type, EventSourcingAnalyzer $analyzer): string
    {
        $path = $this->determinePath($type, $analyzer);

        return $path . DIRECTORY_SEPARATOR . ($this->getFilterClassName())($type->name()) . '.php';
    }

    private function determineAggregate(VertexType $type, AggregateConnectionAnalyzer $analyzer): ?AggregateType
    {
        $aggregate = null;

        switch (true) {
            case $type instanceof CommandType:
                $aggregate = $analyzer->aggregateConnectionMap()->aggregateByCommand($type);

                if ($aggregate === null) {
                    throw new RuntimeException(
                        \sprintf(
                            'Command "%s" has no aggregate connection. Can not use aggregate name for path.',
                            $type->label()
                        )
                    );
                }
                break;
            case $type instanceof EventType:
                $aggregate = $analyzer->aggregateConnectionMap()->aggregateByEvent($type);

                if ($aggregate === null) {
                    throw new RuntimeException(
                        \sprintf(
                            'Event "%s" has no aggregate connection. Can not use aggregate name for path.',
                            $type->label()
                        )
                    );
                }
                break;
            case $type instanceof AggregateType:
                if ($analyzer->aggregateConnectionMap()->has($type->id()) === false) {
                    throw new RuntimeException(
                        \sprintf(
                            'Aggregate "%s" not found in aggregate map. Can not use aggregate name for path.',
                            $type->label()
                        )
                    );
                }
                $aggregate = $analyzer->aggregateConnectionMap()->aggregateConnection($type->id())->aggregate();
                break;
            case $type instanceof DocumentType:
                $aggregate = $analyzer->aggregateConnectionMap()->aggregateByDocument($type);

                if ($aggregate === null) {
                    $metadataInstance = $type->metadataInstance();
                    if ($metadataInstance instanceof HasCustomData) {
                        $aggregateName = ($this->getFilterClassName())($metadataInstance->customData()['aggregate'] ?? '');

                        $aggregateMap = $analyzer->aggregateConnectionMap()->aggregateVertexMap();

                        if ($aggregateMap->has($aggregateName)) {
                            $aggregate = $aggregateMap->vertex($aggregateName);
                        }
                    }
                }
                break;
            default:
                break;
        }

        return $aggregate;
    }

    private function determineFeature(VertexType $type, FeatureConnectionAnalyzer $analyzer): ?FeatureType
    {
        $feature = null;

        switch (true) {
            case $type instanceof CommandType:
                $feature = $analyzer->featureConnectionMap()->featureByCommand($type);

                if ($feature === null) {
                    throw new RuntimeException(
                        \sprintf(
                            'Command "%s" has no feature connection. Can not use feature name for path.',
                            $type->label()
                        )
                    );
                }
                break;
            case $type instanceof EventType:
                $feature = $analyzer->featureConnectionMap()->featureByEvent($type);

                if ($feature === null) {
                    throw new RuntimeException(
                        \sprintf(
                            'Event "%s" has no feature connection. Can not use feature name for path.',
                            $type->label()
                        )
                    );
                }
                break;
            case $type instanceof AggregateType:
                $feature = $analyzer->featureConnectionMap()->featureByAggregate($type);

                if ($feature === null) {
                    throw new RuntimeException(
                        \sprintf(
                            'Aggregate "%s" has no feature connection. Can not use feature name for path.',
                            $type->label()
                        )
                    );
                }
                break;
            case $type instanceof DocumentType:
                $feature = $analyzer->featureConnectionMap()->featureByDocument($type);

                if ($feature === null) {
                    throw new RuntimeException(
                        \sprintf(
                            'Document "%s" has no feature connection. Can not use feature name for path.',
                            $type->label()
                        )
                    );
                }
                break;
            default:
                break;
        }

        return $feature;
    }
}
