<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Config;

use EventEngine\CodeGenerator\EventEngineAst\Code\ObjectGenerator;
use EventEngine\CodeGenerator\EventEngineAst\Exception\RuntimeException;
use EventEngine\InspectioGraph\AggregateType;
use EventEngine\InspectioGraph\CommandType;
use EventEngine\InspectioGraph\EventSourcingAnalyzer;
use EventEngine\InspectioGraph\EventType;
use EventEngine\InspectioGraph\VertexType;
use OpenCodeModeling\Filter\FilterFactory;
use OpenCodeModeling\JsonSchemaToPhpAst\ValueObjectFactory;

final class PreConfiguredAggregate implements Aggregate
{
    private ObjectGenerator $objectGenerator;
    private ValueObjectFactory $valueObjectFactory;

    public function __construct(bool $useAggregateFolder = true, bool $useStoreStateIn = true)
    {
        $this->filterClassName = FilterFactory::classNameFilter();
        $this->filterConstName = FilterFactory::constantNameFilter();
        $this->filterConstValue = FilterFactory::constantValueFilter();
        $this->filterCommandMethodName = FilterFactory::methodNameFilter();
        $this->filterDirectoryToNamespace = FilterFactory::directoryToNamespaceFilter();
        $this->filterNamespaceToDirectory = FilterFactory::namespaceToDirectoryFilter();
        $this->filterParameterMethodName = FilterFactory::propertyNameFilter();

        $this->setFilterAggregateFolder($this->filterClassName);
        $this->injectFilterEventMethodName(FilterFactory::methodNameFilter());
        $this->injectFilterAggregateIdName(FilterFactory::propertyNameFilter());
        $this->injectFilterWithMethodName(FilterFactory::methodNameFilter());
        $this->injectFilterAggregateStateClassName(FilterFactory::classNameFilter());

        if ($useAggregateFolder) {
            $this->setFilterAggregateFolder($this->getFilterClassName());
        }
        if ($useStoreStateIn) {
            $this->injectFilterAggregateStoreStateIn($this->getFilterConstValue());
        }
    }

    use BasePathTrait;
    use ClassInfoListTrait;
    use FilterAggregateFolderTrait;
    use FilterAggregateIdNameTrait;
    use FilterAggregateStateClassNameTrait;
    use FilterAggregateStoreStateInTrait;
    use FilterClassNameTrait;
    use FilterCommandMethodNameTrait;
    use FilterConstNameTrait;
    use FilterConstValueTrait;
    use FilterDirectoryToNamespaceTrait;
    use FilterEventMethodNameTrait;
    use FilterMethodNameTrait;
    use FilterNamespaceToDirectoryTrait;
    use FilterParameterNameTrait;
    use FilterPropertyNameTrait;
    use FilterWithMethodNameTrait;
    use PhpParserTrait;
    use PhpPrinterTrait;

    public function getObjectGenerator(): ObjectGenerator
    {
        if (! isset($this->objectGenerator)) {
            $this->objectGenerator = new ObjectGenerator(
                $this->getClassInfoList(),
                $this->getValueObjectFactory(),
                $this->filterClassName
            );
        }

        return $this->objectGenerator;
    }

    public function getValueObjectFactory(): ValueObjectFactory
    {
        if (! isset($this->valueObjectFactory)) {
            $this->valueObjectFactory = new ValueObjectFactory(
                    $this->getClassInfoList(),
                    $this->getParser(),
                    $this->getPrinter(),
                    true,
                    $this->getFilterClassName(),
                    $this->getFilterPropertyName(),
                    $this->getFilterMethodName(),
                    $this->getFilterConstName(),
                    $this->getFilterConstValue()
            );
        }

        return $this->valueObjectFactory;
    }

    public function determineValueObjectPath(VertexType $type, EventSourcingAnalyzer $analyzer): string
    {
        $path = $this->basePath . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Domain'. DIRECTORY_SEPARATOR . 'Model' . DIRECTORY_SEPARATOR .'ValueObject';

        return $path;
    }

    public function determinePath(VertexType $type, EventSourcingAnalyzer $analyzer): string
    {
        $filterAggregateFolder = $this->getFilterAggregateFolder();

        $path = $this->basePath . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Domain' . DIRECTORY_SEPARATOR . 'Model';

        switch (true) {
            case $type instanceof CommandType:
                if ($filterAggregateFolder === null) {
                    return $path . DIRECTORY_SEPARATOR . 'Command';
                }
                $aggregate = $analyzer->aggregateMap()->aggregateByCommand($type);

                if ($aggregate === null) {
                    throw new RuntimeException(
                        \sprintf('Command "%s" has no aggregate connection. Can not use aggregate name for path.',
                            $type->label())
                    );
                }
                $path .= DIRECTORY_SEPARATOR . ($filterAggregateFolder)($aggregate->label()) . DIRECTORY_SEPARATOR . 'Command';
                break;
            case $type instanceof EventType:
                if ($filterAggregateFolder === null) {
                    return $path . DIRECTORY_SEPARATOR . 'Event';
                }
                $aggregate = $analyzer->aggregateMap()->aggregateByEvent($type);

                if ($aggregate === null) {
                    throw new RuntimeException(
                        \sprintf('Event "%s" has no aggregate connection. Can not use aggregate name for path.',
                            $type->label())
                    );
                }
                $path .= DIRECTORY_SEPARATOR . ($filterAggregateFolder)($aggregate->label()) . DIRECTORY_SEPARATOR . 'Event';
                break;
            case $type instanceof AggregateType:
                if ($filterAggregateFolder === null) {
                    return $path . DIRECTORY_SEPARATOR . 'Aggregate';
                }
                if ($analyzer->aggregateMap()->has($type->name()) === false) {
                    throw new RuntimeException(
                        \sprintf('Aggregate "%s" has no aggregate connection. Can not use aggregate name for path.',
                            $type->label())
                    );
                }
                $aggregate = $analyzer->aggregateMap()->aggregateConnection($type->name())->aggregate();
                $path .= DIRECTORY_SEPARATOR . ($filterAggregateFolder)($aggregate->label());
                break;
            default:
                break;
        }

        return $path;
    }
}
