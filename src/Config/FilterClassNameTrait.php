<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Config;

use OpenCodeModeling\Filter\Filter\Noop;

trait FilterClassNameTrait
{
    /**
     * @var callable
     **/
    private $filterClassName;

    public function getFilterClassName(): callable
    {
        if (null === $this->filterClassName) {
            $this->filterClassName = new Noop();
        }

        return $this->filterClassName;
    }

    public function setFilterClassName(callable $filterClassName): void
    {
        $this->filterClassName = $filterClassName;
    }
}
