<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Config;

use OpenCodeModeling\Filter\Filter\Noop;

trait FilterMethodNameTrait
{
    /**
     * @var callable
     **/
    private $filterMethodName;

    abstract public function getFilterConstValue(): callable;

    public function getFilterMethodName(): callable
    {
        if (null === $this->filterMethodName) {
            $this->filterMethodName = new Noop();
        }

        return $this->filterMethodName;
    }

    public function setFilterMethodName(callable $filterMethodName): void
    {
        $this->filterMethodName = $filterMethodName;
    }
}
