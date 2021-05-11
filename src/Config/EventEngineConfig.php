<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Config;

use EventEngine\CodeGenerator\EventEngineAst\Code\ObjectGenerator;
use OpenCodeModeling\Filter\FilterFactory;
use OpenCodeModeling\JsonSchemaToPhpAst\ValueObjectFactory;

final class EventEngineConfig implements Base
{
    use BasePathTrait;
    use ClassInfoListTrait;
    use DeterminePathTrait;
    use FilterClassNameTrait;
    use FilterConstNameTrait;
    use FilterConstValueTrait;
    use FilterDirectoryToNamespaceTrait;
    use FilterMethodNameTrait;
    use FilterNamespaceToDirectoryTrait;
    use FilterParameterNameTrait;
    use FilterPropertyNameTrait;
    use PhpParserTrait;
    use PhpPrinterTrait;

    private ObjectGenerator $objectGenerator;
    private ValueObjectFactory $valueObjectFactory;

    public function __construct()
    {
        $this->filterClassName = FilterFactory::classNameFilter();
        $this->filterConstName = FilterFactory::constantNameFilter();
        $this->filterConstValue = FilterFactory::constantValueFilter();
        $this->filterMethodName = FilterFactory::methodNameFilter();
        $this->filterPropertyName = FilterFactory::propertyNameFilter();
        $this->filterDirectoryToNamespace = FilterFactory::directoryToNamespaceFilter();
        $this->filterNamespaceToDirectory = FilterFactory::namespaceToDirectoryFilter();
        $this->filterParameterMethodName = FilterFactory::propertyNameFilter();
    }

    public function getObjectGenerator(): ObjectGenerator
    {
        if (! isset($this->objectGenerator)) {
            $this->objectGenerator = new ObjectGenerator($this);
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
}
