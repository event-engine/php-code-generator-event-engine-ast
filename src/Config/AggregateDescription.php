<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Config;

final class AggregateDescription
{
    use ClassInfoListTrait;
    use FilterAggregateFolderTrait;
    use FilterAggregateIdNameTrait;
    use FilterAggregateStoreStateInTrait;
    use FilterClassNameTrait;
    use FilterCommandMethodNameTrait;
    use FilterConstNameTrait;
    use FilterConstValueTrait;
    use FilterDirectoryToNamespaceTrait;
    use FilterEventMethodNameTrait;
    use FilterNamespaceToDirectoryTrait;
    use FilterParameterNameTrait;
    use PhpParserTrait;
    use PhpPrinterTrait;
}
