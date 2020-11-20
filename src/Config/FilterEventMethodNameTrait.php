<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Config;

use EventEngine\CodeGenerator\EventEngineAst\Filter\AggregateBehaviourEventMethod;

trait FilterEventMethodNameTrait
{
    /**
     * @var callable
     **/
    private $filterEventMethodName;

    abstract public function getFilterConstValue(): callable;

    public function getFilterEventMethodName(): callable
    {
        if (null === $this->filterEventMethodName) {
            $this->filterEventMethodName = new AggregateBehaviourEventMethod($this->getFilterConstValue());
        }

        return $this->filterEventMethodName;
    }

    public function setFilterEventMethodName(callable $filterEventMethodName): void
    {
        $this->filterEventMethodName = $filterEventMethodName;
    }
}
