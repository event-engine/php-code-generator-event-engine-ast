<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Filter;

use OpenCodeModeling\Filter\Filter\Noop;

abstract class AbstractFilter implements Filter
{
    /**
     * @var callable
     **/
    protected $filter;

    public function __construct(callable $filter = null)
    {
        $this->filter = $filter ?? new Noop();
    }
}
