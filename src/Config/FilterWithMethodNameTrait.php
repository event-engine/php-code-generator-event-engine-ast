<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Config;

use EventEngine\CodeGenerator\EventEngineAst\Filter\AggregateStateWithMethod;

trait FilterWithMethodNameTrait
{
    /**
     * @var callable
     **/
    private $filterWithMethodName;

    public function injectFilterWithMethodName(callable $filter): void
    {
        $this->filterWithMethodName = new AggregateStateWithMethod($filter);
    }

    public function getFilterWithMethodName(): callable
    {
        if (null === $this->filterWithMethodName) {
            $this->filterWithMethodName = new AggregateStateWithMethod();
        }

        return $this->filterWithMethodName;
    }

    public function setFilterWithMethodName(callable $filterWithMethodName): void
    {
        $this->filterWithMethodName = $filterWithMethodName;
    }
}
