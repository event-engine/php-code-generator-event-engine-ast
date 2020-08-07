<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine\Config;

use OpenCodeModeling\CodeGenerator\Config\ClassInfoListTrait;

final class AggregateState
{
    use ClassInfoListTrait;
    use FilterAggregateFolderTrait;
    use FilterClassNameTrait;
    use FilterConstNameTrait;
    use FilterConstValueTrait;
    use FilterDirectoryToNamespaceTrait;
    use FilterNamespaceToDirectoryTrait;
    use FilterWithMethodNameTrait;
    use PhpParserTrait;
    use PhpPrinterTrait;
}
