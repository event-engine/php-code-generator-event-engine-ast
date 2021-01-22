<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Config;

use EventEngine\CodeGenerator\EventEngineAst\Filter\AggregateStateClassName;

trait FilterAggregateStateClassNameTrait
{
    /**
     * @var callable
     **/
    private $filterAggregateStateClassName;

    public function injectFilterAggregateStateClassName(callable $filter): void
    {
        $this->filterAggregateStateClassName = new AggregateStateClassName($filter);
    }

    public function getFilterAggregateStateClassName(): callable
    {
        if (null === $this->filterAggregateStateClassName) {
            $this->filterAggregateStateClassName = new AggregateStateClassName();
        }

        return $this->filterAggregateStateClassName;
    }

    public function setFilterAggregateStateClassName(callable $filterAggregateStateClassName): void
    {
        $this->filterAggregateStateClassName = $filterAggregateStateClassName;
    }
}
