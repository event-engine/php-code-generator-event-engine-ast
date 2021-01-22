<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Config;

use EventEngine\CodeGenerator\EventEngineAst\Filter\Id;

trait FilterAggregateIdNameTrait
{
    /**
     * @var callable
     **/
    private $filterAggregateIdName;

    public function injectFilterAggregateIdName(callable $filter): void
    {
        $this->filterAggregateIdName = new Id($filter);
    }

    public function getFilterAggregateIdName(): callable
    {
        if (null === $this->filterAggregateIdName) {
            $this->filterAggregateIdName = new Id();
        }

        return $this->filterAggregateIdName;
    }

    public function setFilterAggregateIdName(callable $filterAggregateIdName): void
    {
        $this->filterAggregateIdName = $filterAggregateIdName;
    }
}
