<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Config;

use OpenCodeModeling\Filter\Filter\Noop;

trait FilterConstValueTrait
{
    /**
     * @var callable
     **/
    private $filterConstValue;

    public function getFilterConstValue(): callable
    {
        if (null === $this->filterConstValue) {
            $this->filterConstValue = new Noop();
        }

        return $this->filterConstValue;
    }

    public function setFilterConstValue(callable $filterConstValue): void
    {
        $this->filterConstValue = $filterConstValue;
    }
}
