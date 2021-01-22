<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Config;

use OpenCodeModeling\Filter\FilterFactory;

final class EventDescription
{
    public static function withDefaultConfig(): self
    {
        $self = new self();

        $self->filterClassName = FilterFactory::classNameFilter();
        $self->filterConstName = FilterFactory::constantNameFilter();
        $self->filterConstValue = FilterFactory::constantValueFilter();

        return $self;
    }

    use FilterClassNameTrait;
    use FilterConstNameTrait;
    use FilterConstValueTrait;
    use PhpParserTrait;
    use PhpPrinterTrait;
}
