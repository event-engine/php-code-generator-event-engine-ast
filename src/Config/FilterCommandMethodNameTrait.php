<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Config;

use EventEngine\CodeGenerator\EventEngineAst\Filter\AggregateBehaviourCommandMethod;

trait FilterCommandMethodNameTrait
{
    /**
     * @var callable
     **/
    private $filterCommandMethodName;

    abstract public function getFilterConstValue(): callable;

    public function getFilterCommandMethodName(): callable
    {
        if (null === $this->filterCommandMethodName) {
            $this->filterCommandMethodName = new AggregateBehaviourCommandMethod($this->getFilterConstValue());
        }

        return $this->filterCommandMethodName;
    }

    public function setFilterCommandMethodName(callable $filterCommandMethodName): void
    {
        $this->filterCommandMethodName = $filterCommandMethodName;
    }
}
