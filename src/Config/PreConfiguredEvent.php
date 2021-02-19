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
use EventEngine\InspectioGraph\CommandType;
use EventEngine\InspectioGraph\EventSourcingAnalyzer;
use EventEngine\InspectioGraph\EventType;
use EventEngine\InspectioGraph\VertexType;
use OpenCodeModeling\Filter\FilterFactory;
use OpenCodeModeling\JsonSchemaToPhpAst\ValueObjectFactory;

final class PreConfiguredEvent implements Event
{
    private ObjectGenerator $objectGenerator;
    private ValueObjectFactory $valueObjectFactory;

    public function __construct()
    {
        $this->filterClassName = FilterFactory::classNameFilter();
        $this->filterConstName = FilterFactory::constantNameFilter();
        $this->filterConstValue = FilterFactory::constantValueFilter();
        $this->filterDirectoryToNamespace = FilterFactory::directoryToNamespaceFilter();
        $this->filterNamespaceToDirectory = FilterFactory::namespaceToDirectoryFilter();

        $this->setFilterAggregateFolder($this->filterClassName);
    }

    use BasePathTrait;
    use ClassInfoListTrait;
    use FilterAggregateFolderTrait;
    use FilterClassNameTrait;
    use FilterConstNameTrait;
    use FilterConstValueTrait;
    use FilterPropertyNameTrait;
    use FilterMethodNameTrait;
    use FilterDirectoryToNamespaceTrait;
    use FilterNamespaceToDirectoryTrait;
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

        $path = $this->basePath . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Domain'. DIRECTORY_SEPARATOR . 'Model';

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
            default:
                break;
        }

        return $path;
    }
}
