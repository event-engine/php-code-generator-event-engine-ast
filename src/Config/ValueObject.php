<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Config;

use OpenCodeModeling\Filter\FilterFactory;

final class ValueObject
{
    public static function withDefaultConfig(): self
    {
        $self = new self();

        $self->filterClassName = FilterFactory::classNameFilter();
        $self->filterConstName = FilterFactory::constantNameFilter();
        $self->filterConstValue = FilterFactory::constantValueFilter();
        $self->filterDirectoryToNamespace = FilterFactory::directoryToNamespaceFilter();
        $self->filterMethodName = FilterFactory::methodNameFilter();
        $self->filterNamespaceToDirectory = FilterFactory::namespaceToDirectoryFilter();
        $self->filterPropertyName = FilterFactory::propertyNameFilter();

        return $self;
    }

    use BasePathTrait;
    use ClassInfoListTrait;
    use FilterClassNameTrait;
    use FilterConstNameTrait;
    use FilterConstValueTrait;
    use FilterDirectoryToNamespaceTrait;
    use FilterMethodNameTrait;
    use FilterNamespaceToDirectoryTrait;
    use FilterPropertyNameTrait;
    use FilterValueObjectFolderTrait;
    use PhpParserTrait;
    use PhpPrinterTrait;
}
