<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Config;

use OpenCodeModeling\Filter\FilterFactory;

final class Command
{
    public static function withDefaultConfig(): self
    {
        $self = new self();

        $self->filterClassName = FilterFactory::classNameFilter();
        $self->filterConstName = FilterFactory::constantNameFilter();
        $self->filterConstValue = FilterFactory::constantValueFilter();
        $self->filterDirectoryToNamespace = FilterFactory::directoryToNamespaceFilter();
        $self->filterNamespaceToDirectory = FilterFactory::namespaceToDirectoryFilter();

        return $self;
    }

    use BasePathTrait;
    use ClassInfoListTrait;
    use FilterAggregateFolderTrait;
    use FilterClassNameTrait;
    use FilterCommandFolderTrait;
    use FilterConstNameTrait;
    use FilterConstValueTrait;
    use FilterDirectoryToNamespaceTrait;
    use FilterNamespaceToDirectoryTrait;
    use PhpParserTrait;
    use PhpPrinterTrait;
}
