<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Config;

use OpenCodeModeling\Filter\Filter\Noop;

trait FilterPropertyNameTrait
{
    /**
     * @var callable
     **/
    private $filterPropertyName;

    abstract public function getFilterConstValue(): callable;

    public function getFilterPropertyName(): callable
    {
        if (null === $this->filterPropertyName) {
            $this->filterPropertyName = new Noop();
        }

        return $this->filterPropertyName;
    }

    public function setFilterPropertyName(callable $filterPropertyName): void
    {
        $this->filterPropertyName = $filterPropertyName;
    }
}
