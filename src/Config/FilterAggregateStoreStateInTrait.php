<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Config;

use EventEngine\CodeGenerator\EventEngineAst\Filter\StateName;

trait FilterAggregateStoreStateInTrait
{
    /**
     * @var callable
     **/
    private $filterAggregateStoreStateIn;

    public function injectFilterAggregateStoreStateIn(callable $filter): void
    {
        $this->filterAggregateStoreStateIn = new StateName($filter);
    }

    public function getFilterAggregateStoreStateIn(): ?callable
    {
        if (null === $this->filterAggregateStoreStateIn) {
            $this->filterAggregateStoreStateIn = new StateName();
        }

        return $this->filterAggregateStoreStateIn;
    }

    public function setFilterAggregateStoreStateIn(?callable $filterAggregateStoreStateIn): void
    {
        $this->filterAggregateStoreStateIn = $filterAggregateStoreStateIn;
    }
}
