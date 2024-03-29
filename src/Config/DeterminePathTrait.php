<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Config;

use EventEngine\CodeGenerator\EventEngineAst\Exception\RuntimeException;
use EventEngine\CodeGenerator\EventEngineAst\Helper\FindAggregateTrait;
use EventEngine\CodeGenerator\EventEngineAst\Helper\MetadataCustomTrait;
use EventEngine\InspectioGraph\AggregateType;
use EventEngine\InspectioGraph\CommandType;
use EventEngine\InspectioGraph\DocumentType;
use EventEngine\InspectioGraph\EventSourcingAnalyzer;
use EventEngine\InspectioGraph\EventType;
use EventEngine\InspectioGraph\FeatureType;
use EventEngine\InspectioGraph\Metadata\HasCustomData;
use EventEngine\InspectioGraph\VertexType;

trait DeterminePathTrait
{
    use MetadataCustomTrait;
    use FindAggregateTrait;

    abstract public function getBasePath(): string;

    abstract public function getFilterClassName(): callable;

    public function determineValueObjectPath(VertexType $type, EventSourcingAnalyzer $analyzer): string
    {
        switch (true) {
            case $type instanceof CommandType:
            case $type instanceof EventType:
            case $type instanceof AggregateType:
            case $type instanceof DocumentType:
            default:
                $namespace = $this->determineValueObjectNamespace($type);
                $namespace = \str_replace('\\', '//', \trim($namespace, '\\'));

                if ($namespace !== '') {
                    return $this->determineValueObjectSharedPath() . DIRECTORY_SEPARATOR . $namespace;
                }

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
        $namespace = $this->determineNamespace($type, $analyzer);

        switch (true) {
            case $type instanceof CommandType:
                return $this->determineDomainPath($type, $analyzer) . DIRECTORY_SEPARATOR . 'Command' . $namespace;
            case $type instanceof EventType:
                return $this->determineDomainPath($type, $analyzer) . DIRECTORY_SEPARATOR . 'Event' . $namespace;
            case $type instanceof AggregateType:
                return $this->determineDomainPath($type, $analyzer) . $namespace;
            case $type instanceof DocumentType:
                return $this->determineValueObjectSharedPath() . $namespace;
            default:
                throw new RuntimeException(
                    \sprintf('Can not determine path for sticky type "%s"', \get_class($type))
                );
        }
    }

    public function determineQueryPath(DocumentType $type, EventSourcingAnalyzer $analyzer): string
    {
        $namespace = $this->determineNamespace($type, $analyzer);

        return $this->determineInfrastructureRoot() . '\\Resolver' . $namespace . '\\Query';
    }

    public function determineResolverPath(DocumentType $type, EventSourcingAnalyzer $analyzer): string
    {
        $namespace = $this->determineNamespace($type, $analyzer);

        return $this->determineInfrastructureRoot() . '\\Resolver' . $namespace;
    }

    public function determineFinderPath(DocumentType $type, EventSourcingAnalyzer $analyzer): string
    {
        $namespace = $this->determineNamespace($type, $analyzer);

        return $this->determineInfrastructureRoot() . '\\Finder' . $namespace;
    }

    public function determineSchemaRoot(): string
    {
        return $this->determineDomainRoot() . DIRECTORY_SEPARATOR . 'Api' . DIRECTORY_SEPARATOR . '_schema';
    }

    public function determineSchemaFilename(VertexType $type, EventSourcingAnalyzer $analyzer): string
    {
        $path = $this->determineSchemaPath($type, $analyzer);

        return $path . DIRECTORY_SEPARATOR . ($this->getFilterClassName())($type->name()) . '.json';
    }

    public function determineQuerySchemaFilename(VertexType $type, EventSourcingAnalyzer $analyzer): string
    {
        $schemaPath = $this->determineSchemaRoot();
        $namespace = $this->determineNamespace($type, $analyzer);

        if ($type instanceof DocumentType) {
            return $schemaPath . DIRECTORY_SEPARATOR . 'Query' . $namespace . DIRECTORY_SEPARATOR. ($this->getFilterClassName())($type->name()) . '.json';
        }

        throw new RuntimeException(
            \sprintf('Can not determine query JSON schema path for sticky type "%s"', \get_class($type))
        );
    }

    public function determineSchemaPath(VertexType $type, EventSourcingAnalyzer $analyzer): string
    {
        $schemaPath = $this->determineSchemaRoot();
        $schemaPathAggregate = $schemaPath;

        $filterFolder = $this->getFilterClassName();

        $aggregate = $this->determineAggregate($type, $analyzer);

        if ($aggregate !== null) {
            $schemaPathAggregate .= DIRECTORY_SEPARATOR . ($filterFolder)($aggregate->label());
        }

        $namespace = $this->determineNamespace($type, $analyzer);

        switch (true) {
            case $type instanceof CommandType:
                return $schemaPathAggregate . DIRECTORY_SEPARATOR . 'Command' . $namespace;
            case $type instanceof EventType:
                return $schemaPathAggregate . DIRECTORY_SEPARATOR . 'Event' . $namespace;
            case $type instanceof AggregateType:
                return $schemaPathAggregate . $namespace;
            case $type instanceof DocumentType:
                return $schemaPath . DIRECTORY_SEPARATOR . 'ValueObject' . $namespace;
            default:
                throw new RuntimeException(
                    \sprintf('Can not determine JSON schema path for sticky type "%s"', \get_class($type))
                );
        }
    }

    private function determineNamespace(VertexType $type, EventSourcingAnalyzer $analyzer): string
    {
        $namespace = $this->getCustomMetadata($type, 'namespace') ?? '';

        if ($namespace === '') {
            $namespace = $this->getCustomMetadata($type, 'ns') ?? '';
        }
        $namespace = \str_replace('/', '\\', $namespace);

        if ($namespace === '' && $type instanceof DocumentType) {
            $namespace = $this->determineValueObjectNamespace($type);
        }
        if ($namespace !== '') {
            $namespace = DIRECTORY_SEPARATOR . \str_replace('\\', '/', \trim($namespace, '\\'));

            if ($namespace === '/') {
                $namespace = '';
            }
        }

        return $namespace;
    }

    public function determineFilename(VertexType $type, EventSourcingAnalyzer $analyzer): string
    {
        $path = $this->determinePath($type, $analyzer);

        return $path . DIRECTORY_SEPARATOR . ($this->getFilterClassName())($type->name()) . '.php';
    }

    private function findFeature(VertexType $type, EventSourcingAnalyzer $analyzer): ?FeatureType
    {
        $connection = $analyzer->connection($type->id());

        if (($parent = $connection->parent())
            && $parent->type() === VertexType::TYPE_FEATURE
        ) {
            // @phpstan-ignore-next-line
            return $parent;
        }

        return null;
    }

    private function determineAggregate(VertexType $type, EventSourcingAnalyzer $analyzer): ?AggregateType
    {
        $aggregate = null;

        switch (true) {
            case $type instanceof CommandType:
                $aggregate = $this->findAggregate($type->id(), $analyzer);

                if ($aggregate === null) {
                    $metadataInstance = $type->metadataInstance();
                    if ($metadataInstance instanceof HasCustomData) {
                        $aggregateName = ($this->getFilterConstName())($metadataInstance->customData()['aggregate'] ?? '');

                        $aggregate = $analyzer->aggregateMap()->filterByName($aggregateName)->current() ?: null;
                    }
                }

                if ($aggregate === null) {
                    throw new RuntimeException(
                        \sprintf(
                            'Command "%s" has no aggregate connection. Can not use aggregate name for path.',
                            $type->label()
                        )
                    );
                }
                $aggregate = $aggregate->identity();

                break;
            case $type instanceof EventType:
                $aggregate = $this->findAggregate($type->id(), $analyzer);

                if ($aggregate === null) {
                    $metadataInstance = $type->metadataInstance();
                    if ($metadataInstance instanceof HasCustomData) {
                        $aggregateName = ($this->getFilterConstName())($metadataInstance->customData()['aggregate'] ?? '');

                        $aggregate = $analyzer->aggregateMap()->filterByName($aggregateName)->current() ?: null;
                    }
                }
                if ($aggregate === null) {
                    throw new RuntimeException(
                        \sprintf(
                            'Event "%s" has no aggregate connection. Can not use aggregate name for path.',
                            $type->label()
                        )
                    );
                }
                $aggregate = $aggregate->identity();

                break;
            case $type instanceof AggregateType:
                if ($analyzer->has($type->id()) === false) {
                    throw new RuntimeException(
                        \sprintf(
                            'Aggregate "%s" not found in aggregate map. Can not use aggregate name for path.',
                            $type->label()
                        )
                    );
                }
                $aggregate = $analyzer->connection($type->id())->identity();

                break;
            case $type instanceof DocumentType:
                $aggregate = $this->findAggregate($type->id(), $analyzer);

                if ($aggregate === null) {
                    $metadataInstance = $type->metadataInstance();
                    if ($metadataInstance instanceof HasCustomData) {
                        $aggregateName = ($this->getFilterConstName())($metadataInstance->customData()['aggregate'] ?? '');

                        $aggregate = $analyzer->aggregateMap()->filterByName($aggregateName)->current() ?: null;

                        // TODO check from / to connections with depth
                    }
                }
                $aggregate = $aggregate ? $aggregate->identity() : null;

                break;
            default:
                break;
        }

        return $aggregate;
    }

    private function determineFeature(VertexType $type, EventSourcingAnalyzer $analyzer): ?FeatureType
    {
        $feature = null;

        switch (true) {
            case $type instanceof CommandType:
                $feature = $this->findFeature($type, $analyzer);

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
                $feature = $this->findFeature($type, $analyzer);

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
                $feature = $this->findFeature($type, $analyzer);

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
                $feature = $this->findFeature($type, $analyzer);

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

    private function determineValueObjectNamespace(VertexType $type): string
    {
        $namespace = $this->getCustomMetadata($type, 'voNamespace') ?? '';

        return \str_replace('/', '\\', $namespace);
    }
}
